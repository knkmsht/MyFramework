<?php
/**
 * html 產生器
 * 1. 直接寫的 js 要在 return 時輸出, 因為  dynamictable 會將輸出的 js 做處理, 而引用的 js 就使用 set_js
 * 2. 為了配合 dynamictable 能處理各函式輸出的 js、css, 所以採用 retrun array($html, $js);
 * 3. 不在 html 裡寫 onclick 而獨立出來, 是因為 dynamictable 會用 js 處理輸出的 html, 此時 onclick 的值在單雙引號的跳脫處理就顯得麻煩
 * 4. 必須要這樣 $("body").on(events [, selector ]); (推測不一定要 body, 一個已存在且不會變動的父元素即可), 才能讓 dynamictable 產生的 html 也綁到事件
 * 5. 改為 element[id=\'xxx\'] 是因為當 id 有特殊字元時需要跳脫處理, 而以此寫法則不用, 參考 http://stackoverflow.com/questions/8404037/jquery-escape-square-brackets-to-select-element
 * 6. 將元素平行、垂直置中目前最佳做法
 *     (1) 將欲置中元素的上層元素 css 加上
 *         xxx:before {
 *             content: "";
 *             display: inline-block;
 *             height: inherit;
 *             vertical-align: middle;
 *         }
 *     (2) 將欲置中元素 css 加上
 *         xxx {
 *             display: inline-block;
 *             text-align: center;
 *             vertical-align: middle;
 *             width: inherit;
 *         }
 * 7. 不要在 js 碼裡寫 // 備註, 避免 dynamictable 異常
 * 8. static $pass 裡的 css、js 就直接給 static $css、$js, 避免 dynamictable 重複寫入 dom
 * @author lion
 */
namespace Lib;
class html {
	public static $css_src = [];
	public static $css = null;
	public static $js_src = [];
	public static $js = null;
	
	function __construct() {
	}
	
	function a($attr, $text) {
		return array('<a '.$attr.'>'.$text.'</a>', null);
	}
	
	function back($attr) {
		return array('<input type="button" onclick="history.go(-1);return true;" '.$attr.'>', null);
	}
	
	function browseKit(array $attr) {
		$selector = isset($attr['selector'])? $attr['selector'] : null;
		if ($selector === null) throw new Exception('Parameters error');
		
		static $pass;$html = null;$js = null;
		
		if (!$pass) {
			$pass = true;
			
			$this->set_css(URL_STATIC_FILE.M_PACKAGE.'/'.SITE_LANG.'/js/lightGallery-master/dist/css/lightgallery-modify.min.css', 'href');
        	$this->set_js('https://cdn.jsdelivr.net/picturefill/2.3.1/picturefill.min.js', 'src');
			$this->set_js(URL_STATIC_FILE.M_PACKAGE.'/'.SITE_LANG.'/js/lightGallery-master/dist/js/lightgallery-all-modify.min.js', 'src');
			$this->set_js(URL_STATIC_FILE.M_PACKAGE.'/'.SITE_LANG.'/js/lightGallery-master/lib/jquery.mousewheel.min.js', 'src');
		}
		$js .= '
			$(function() {
				$(document).on("click", "'.$selector.'", function(e) {
				    e.preventDefault();
		
					var a_item = [], that0 = $(this), index = 0;
					
					$("'.$selector.'").each(function(k0, v0) {
						var that1 = $(this);
	
					    a_item.push({
					        src: that1.prop("href"),
					        thumb: that1.find("img").prop("src"),
					        subHtml: that1.data("name"),
					    });
							
						if (that0.is(that1)) index = k0;
					});
						
				    that0.lightGallery({
				        dynamic: true,
				        dynamicEl: a_item,
						hash: false,
						hideBarsDelay: 3000,
						loop: false,
						thumbWidth: 80,
						index: index,
						youtubePlayerParams: {
					        showinfo: 0,
					        rel: 0,
						},
				    })
				});
			});';
		
		return [$html, $js];
	}
	
	function button($attr) {
		return ['<input type="button" '.$attr.'>', null];
	}
	
	function canvas($attr) {
		return ['<canvas '.$attr.'>'._('Your browser doesn\'t support the HTML5 canvastag').'</canvas>', null];
	}
	
	function chartKit() {
		static $pass;
		if (!$pass) {
			$pass = true;
			$this->set_js(URL_STATIC_FILE.M_PACKAGE.'/'.SITE_LANG.'/js/Highcharts-4.2.3/js/highcharts.js', 'src');
			$this->set_js(URL_STATIC_FILE.M_PACKAGE.'/'.SITE_LANG.'/js/Highcharts-4.2.3/js/modules/exporting.js', 'src');
			
			self::$js .= '
			Highcharts.setOptions({
				global: {
					useUTC: false
				}
			});';
		}
	}
	
	function checkbox($attr, $text=null) {
		static $pass;$html = null;$js = null;
		if (!$pass) {
			$pass = true;
			self::$css .= '
			.checkbox_label {
				cursor: pointer;
				transition: all 0.3s ease 0s;
			}
			:checked + .checkbox_label {
				color: #3799FF;
			}
			';
		}
		$id = explode_attr($attr, 'id', '"');
		if (empty($id)) {
			$id = uniqid();
			$attr = 'id="'.$id.'" '.$attr;
		}
		$html .= '<input type="checkbox" '.$attr.'><label class="checkbox_label" for="'.$id.'">&nbsp;'.$text.'</label>';
	
		return [$html, $js];
	}
	
	function checkedtable($a_attr, $type_1, $a_level_1, $type_2=null, $a_level_2=array(), $type_3=null, $a_level_3=array()) {
		static $pass;$html = null;$js = null;
		$width = isset($a_attr['width'])? $a_attr['width'] : 160;
		$height = isset($a_attr['height'])? $a_attr['height'] : 40;
		$col = isset($a_attr['col'])? $a_attr['col'] : 4;
		$width_align = ($width + 1) * $col.'px';//加 1 是 border 的 1px
		$width = $width.'px';
		$height = $height.'px';
		if (!$pass) {
			$pass = true;
			self::$css .= '
			.checkedtable-div ul:before, .checkedtable-div li:before {
				content: "";
				display: inline-block;
				height: inherit;
				vertical-align: middle;
			}
			.checkedtable-div {
				border: 1px solid black;
				height: inherit;
			}
			.checkedtable-div ul {
				width: inherit;
			}
			.checkedtable-div ul:last-child {
			    border: none;
			}
			.checkedtable-div li {
				display: inline-block;
				vertical-align: middle;
			}
			.checkedtable-v1-ul {
				background-color: #E0EEE0;
				border-bottom: 1px solid #BBBBBB;
			    border-top: 1px solid #000000;
			    height: 30px;
			}
			.checkedtable-v1-ul:first-child {
			    border-top: none;
			}
			.checkedtable-v1-li {
			    font-weight: bold;
			    height: 30px;
			    width: auto;
			}
			.checkedtable-v2-li:last-child {
			    border-right: none;
			}
			.checkedtable-v2-li {
				border-right: 1px solid #BBBBBB;
			    float: left;
			}
			.checkedtable-v2-li-span, .checkedtable-v3-ul {
				display: inline-block;
				vertical-align: middle;
				width: inherit;
			}
			';
			self::$js .= '
			function checkAll(n, datatype) {
				if (typeof datatype === "object") {
					$("."+n).prop("checked", $(datatype).prop("checked"));
				} else if (typeof datatype === "boolean") {
					$("."+n).prop("checked", datatype);
				}
			}
			function checkUp(obj, n) {
				var index = 0;
				var check = false;
				$.each($("."+n), function(i, v) {
					++index;
					if (true == $(v).prop("checked")) {
						$(".up_"+n).prop("checked", true);
						check = true;
					} else if (index == $("."+n).length && check != true) {
						$(".up_"+n).prop("checked", false);
					}
				});
			}
			';
		}
		$html .= '<div class="checkedtable-div" style="width: '.$width_align.'">';//內嵌 style 是因為要能隨設定變化 
		if (!empty($type_3)) {
			
		} elseif (!empty($type_2)) {
			/**
			 * example
			 * $a_level_1[] = array('key'=>1, 'name'=>'admingroup_id', 'value'=>1, 'text'=>'全選');
			 * $a_level_2[key][] = array('name'=>'admingroup_id', 'value'=>1, 'text'=>'程式', 'checked'=>true);
			 * $a_level_2[key][] = array('name'=>'admingroup_id', 'value'=>2, 'text'=>'行銷', 'checked'=>false);
			 */
			foreach ($a_level_1 as $v1) {
				$index = 0;
				$key = $v1['key'];
				$uniqid = uniqid('checkedtable-');
				$name = !empty($v1['name'])? 'name="'.$v1['name'].'"' : '';
				$value = !empty($v1['value'])? 'value="'.$v1['value'].'"' : '';
				$text = !empty($v1['text'])? $v1['text'] : '全選';
				$checked = ($v1['checked'])? 'checked="checked"' : '';
	
				list($tmp_html, $tmp_js) = $this->$type_1('id="'.$uniqid.'" class="up_'.$v1['name'].$v1['value'].'" '.$name.' '.$value.' '.$checked.' onclick="checkAll(\''.$v1['name'].$v1['value'].'\', this)"', $text);
				$html .= '<ul class="checkedtable-v1-ul"><li class="checkedtable-v1-li">'.$tmp_html.'</li></ul>';
				$js .= $tmp_js;
				
				if (!empty($a_level_2[$key])) {
					foreach ($a_level_2[$key] as $v2) {
						$id = !empty($v2['id'])? $v2['id'] : uniqid('checkedtable-');
						$name = !empty($v2['name'])? 'name="'.$v2['name'].'"' : '';
						$value = !empty($v2['value'])? 'value="'.$v2['value'].'"' : '';
						$text = !empty($v2['text'])? $v2['text'] : '';
						$checked = (isset($v2['checked']) && $v2['checked'])? 'checked="checked"' : '';
						if ($index % $col == 0) {
							$html .= '<ul class="checkedtable-v2-ul" style="height: '.$height.'">';//內嵌 style 是因為要能隨設定變化
						}
	
						list($tmp_html, $tmp_js) = $this->$type_2('id="'.$id.'" class="'.$v1['name'].$v1['value'].'" '.$name.' '.$value.' '.$checked.' onclick="checkUp(this, \''.$v1['name'].$v1['value'].'\')"', $text);
						$html .= '<li class="checkedtable-v2-li" style="height: '.$height.'; width: '.$width.'"><span class="checkedtable-v2-li-span">'.$tmp_html.'</span></li>';//內嵌 style 是因為要能隨設定變化
						$js .= $tmp_js;
	
						if (($index + 1) % $col == 0 || ($index + 1) == count($a_level_2[$key])) {
							$html .= '</ul>';
						}
						++$index;
					}
				}
			}
		} else {
			/**
			 * example
			 * $a_level_1[] = array('name'=>'admingroup_id', 'value'=>1, 'text'=>'程式');
			 * $a_level_1[] = array('name'=>'admingroup_id', 'value'=>2, 'text'=>'行銷');
			 */
			$index = 0;
			$uniqid = uniqid('checkedtable-');
			$html .= '
			<ul class="checkedtable-v1-ul">
			<li class="checkedtable-v1-li">
			<span style="cursor:pointer;" onclick="checkAll(\''.$uniqid.'\', true)">'._('All').'</span>&emsp;/&emsp;<span style="cursor:pointer;" onclick="checkAll(\''.$uniqid.'\', false)">'._('Cancel').'</span>
			</li>
			</ul>
			';
			foreach ($a_level_1 as $v1) {
				$id = !empty($v1['id'])? $v1['id'] : uniqid('checkedtable-');
				$name = !empty($v1['name'])? 'name="'.$v1['name'].'"' : '';
				$value = !empty($v1['value'])? 'value="'.$v1['value'].'"' : '';
				$text = !empty($v1['text'])? $v1['text'] : '';
				$checked = (isset($v1['checked']) && $v1['checked'])? 'checked="checked"' : '';
				if ($index % $col == 0) {
					$html .= '<ul class="checkedtable-v2-ul" style="height: '.$height.'">';//內嵌 style 是因為要能隨設定變化
				}
				
				list($tmp_html, $tmp_js) = $this->$type_1('id="'.$id.'" class="'.$uniqid.'" '.$name.' '.$value.' '.$checked, $text);
				$html .= '<li class="checkedtable-v2-li" style="height: '.$height.'; width: '.$width.'"><span class="checkedtable-v2-li-span">'.$tmp_html.'</span></li>';//內嵌 style 是因為要能隨設定變化
				$js .= $tmp_js;
	
				if (($index + 1) % $col == 0 || ($index + 1) == count($a_level_1)) {
					$html .= '</ul>';
				}
				++$index;
			}
		}
		$html .= '</div>';
		
		return array($html, $js);
	}
	
	function checkboxtable($width, $height, $col, $a_lv1, $a_lv2=array(), $a_lv3=array()) {
		static $pass;$html = null;$js = null;
		if (!$pass) {
			$pass = true;
			self::$css .= '
			.checkboxtable_div {
			    border-left: 1px solid;
			    border-right: 1px solid;
			    border-top: 1px solid;
			}
			.checkboxtable_div ul {
			    border-bottom: 1px solid;
			    height: auto;
			    width: inherit;
			}
			.checkboxtable_td_v1 {
			    background-color: #E0EEE0;
			    display: table-cell;
			    font-weight: bold;
			    padding-left: 8px;
			    vertical-align: middle;
			    width: inherit;
			    height: 30px;
			}
			.checkboxtable_td_v2 {
			    display: table-cell;
			    padding-left: 8px;
			    vertical-align: middle;
			    width: inherit;
			}
			.checkboxtable_span_a {
				float: left;
				width: 90px;
			}
			.checkboxtable_span_b {
				float: left;
			}
			';
				
			self::$js .= '
			function checkAll(n, datatype) {
				if (typeof datatype === "object") {
					$("."+n).prop("checked", $(datatype).prop("checked"));
				} else if (typeof datatype === "boolean") {
					$("."+n).prop("checked", datatype);
				}
			}
			function checkUp(obj, n) {
				var index = 0;
				var check = false;
				$.each($("."+n), function(i, v) {
					++index;
					if (true == $(v).prop("checked")) {
						$(".up_"+n).prop("checked", true);
						check = true;
					} else if (index == $("."+n).length && check != true) {
						$(".up_"+n).prop("checked", false);
					}
				});
			}
			';
		}
		$html .= '<div class="checkboxtable_div" style="width:'.((int)$width * $col).'px;">';
		if (!empty($a_lv3)) {
	
		} elseif (!empty($a_lv2)) {
			/*
			 * example
			* $a_lv1[] = array('key' => 1, 'name' => 'admingroup_id', 'value' => 1, 'text' => '全選');
			*
			* $a_lv2[key][] = array('name' => 'admingroup_id', 'value' => 1, 'text' => '程式');
			* $a_lv2[key][] = array('name' => 'admingroup_id', 'value' => 2, 'text' => '行銷');
			*/
			foreach ($a_lv1 as $v1) {
				$index = 0;
				$key = $v1['key'];
				$uniqid = uniqid(__FUNCTION__);
				$name = !empty($v1['name'])? 'name="'.$v1['name'].'"' : '';
				$value = !empty($v1['value'])? 'value="'.$v1['value'].'"' : '';
				$text = !empty($v1['text'])? $v1['text'] : '全選';
				$checked = ($v1['checked'])? 'checked="checked"' : '';
	
				list($tmp_html, $tmp_js) = $this->checkbox('id="'.$uniqid.'" class="up_'.$v1['name'].$v1['value'].'" '.$name.' '.$value.' '.$checked.' onclick="checkAll(\''.$v1['name'].$v1['value'].'\', this)"', $text);
				$html .= '
				<ul><li class="checkboxtable_td_v1">'.$tmp_html.'</li></ul>
				';
				$js .= $tmp_js;
	
				if (!empty($a_lv2[$key])) {
					foreach ($a_lv2[$key] as $v2) {
						$id = !empty($v2['id'])? $v2['id'] : uniqid(__FUNCTION__);
						$name = !empty($v2['name'])? 'name="'.$v2['name'].'"' : '';
						$value = !empty($v2['value'])? 'value="'.$v2['value'].'"' : '';
						$text = !empty($v2['text'])? $v2['text'] : '';
						$checked = (isset($v2['checked']) && $v2['checked'])? 'checked="checked"' : '';
						$extra = !empty($v2['extra'])? $v2['extra'] : '';
						if ($index % $col == 0) {
							$html .= '<ul>';
							$style = 'style="height:'.$height.';"';
						} else {
							$style = 'style="height:'.$height.';border-left:1px solid #BBBBBB;"';
						}
	
						list($tmp_html, $tmp_js) = $this->checkbox('id="'.$id.'" class="'.$v1['name'].$v1['value'].'" '.$name.' '.$value.' '.$checked.' onclick="checkUp(this, \''.$v1['name'].$v1['value'].'\')"', $text);
	
						$html .= '
						<li '.$style.' class="checkboxtable_td_v2"><span class="checkboxtable_span_a">'.$tmp_html.'</span><span class="checkboxtable_span_b">'.$extra.'</span></li>
						';
						$js .= $tmp_js;
	
						if (($index + 1) % $col == 0 || ($index + 1) == count($a_lv2[$key])) {
							$html .= '</ul>';
						}
						++$index;
					}
				}
			}
		} else {
			/*
			 * example
			* $a_lv1[] = array('name' => 'admingroup_id', 'value' => 1, 'text' => '程式');
			* $a_lv1[] = array('name' => 'admingroup_id', 'value' => 2, 'text' => '行銷');
			*/
			$index = 0;
			$uniqid = uniqid(__FUNCTION__);
			$html .= '
			<ul>
			<li class="checkboxtable_td_v1">
			<label style="cursor:pointer;" onclick="checkAll(\'checkAll'.$uniqid.'\', true)">'._('All').'</label>&nbsp;/&nbsp;<label style="cursor:pointer;" onclick="checkAll(\'checkAll'.$uniqid.'\', false)">'._('Cancel').'</label>
			</li>
			</ul>
			';
			foreach ($a_lv1 as $v1) {
				$id = !empty($v1['id'])? $v1['id'] : uniqid(__FUNCTION__);
				$name = !empty($v1['name'])? 'name="'.$v1['name'].'"' : '';
				$value = !empty($v1['value'])? 'value="'.$v1['value'].'"' : '';
				$text = !empty($v1['text'])? $v1['text'] : '';
				$checked = (isset($v1['checked']) && $v1['checked'])? 'checked="checked"' : '';
				$extra = !empty($v1['extra'])? $v1['extra'] : '';
				if ($index % $col == 0) {
					$html .= '<ul>';
					$style = 'style="height:'.$height.';"';
				} else {
					$style = 'style="height:'.$height.';border-left:1px solid #BBBBBB;"';
				}
	
				list($tmp_html, $tmp_js) = $this->checkbox('id="'.$id.'" class="checkAll'.$uniqid.'" '.$name.' '.$value.' '.$checked, $text);
				$html .= '
				<li '.$style.' class="checkboxtable_td_v2"><span class="checkboxtable_span_a">'.$tmp_html.'</span><span class="checkboxtable_span_b">'.$extra.'</span></li>
				';
				$js .= $tmp_js;
				
				if (($index + 1) % $col == 0 || ($index + 1) == count($a_lv1)) {
					$html .= '</ul>';
				}
				++$index;
			}
		}
		$html .= '</div>';
	
		return array($html, $js);
	}
	
	/**
	 * ckeditor
	 * <p>v1.0 2014-12-05 Lion:
	 *     增加 disallowedContent : "img{width,height}" 制止 img 的 style, 讓 css 得以作用
	 *     參考 http://stackoverflow.com/questions/2051896/ckeditor-prevent-adding-image-dimensions-as-a-css-style
	 * </p>
	 * @param unknown $attr
	 * @param string $text
	 * @throws Exception
	 * @return multitype:string
	 */
	function ckeditor($attr, $text=null) {
		static $pass;$html = null;$js = null;$id = explode_attr($attr, 'id', '"');
		if (empty($id)) {
			throw new Exception("[".__METHOD__."] Parameters error");
		}
		if (!$pass) {
			$pass = true;
			$this->set_js(URL_ROOT.'js/ckeditor_4.4.5_full/ckeditor.js', 'src');
		}
		$html.= '<textarea '.$attr.'>'.$text.'</textarea>';
		$js .= '
		$(window).load(function(){
			CKEDITOR.replace("'.$id.'", {
				filebrowserUploadUrl : "'.\Core::controller()->url('upload', 'ckeditor', array('class'=>M_CLASS)).'",
				filebrowserImageUploadUrl : "'.\Core::controller()->url('upload', 'ckeditor', array('class'=>M_CLASS)).'",
				filebrowserFlashUploadUrl : "'.\Core::controller()->url('upload', 'ckeditor', array('class'=>M_CLASS)).'",
				enterMode : CKEDITOR.ENTER_BR,
				shiftEnterMode: CKEDITOR.ENTER_P,
				disallowedContent : "img{width,height}"
			});
		});
		';
		
		return array($html, $js);
	}
	
	function color($attr) {
		static $pass;$html = null;$js = null;
		
		$id = explode_attr($attr, 'id', '"');
		$value = explode_attr($attr, 'value', '"');
		
		if (!$pass) {
			$pass = true;
			$this->set_css(URL_ROOT.'js/colpick-2014.7.16/colpick.css', 'href');
			$this->set_js(URL_ROOT.'js/colpick-2014.7.16/colpick.js', 'src');
			
			self::$css .= '
			.color-div {
				height: 70px;
				width: inherit;
			}
			.color-div:before  {
				content: "";
				display: inline-block;
				height: inherit;
				vertical-align: middle;
			}
			input[data-kit="color"] {
				display: inline-block;
				margin: 0 5px 0 0;
    			vertical-align: middle;
			}
			input[data-kit="color"] + div {
				border: 1px solid black;
				display: inline-block;
				height: 50px;
    			vertical-align: middle;
				width: 50px;
			}
			';
			
			self::$js .= '
			$("input[data-kit=\'color\']").colpick({
				colorScheme: "dark",
				onChange: function(hsb, hex, rgb, el, bySetColor) {
					$(el).next("div").css("background-color", "#" + hex);
					
					//Fill the text box just if the color was set using the picker, and not the colpickSetColor function.
					if (!bySetColor) $(el).val("#" + hex);
				},
				onSubmit: function(hsb, hex, rgb, el) {
					$(el).val("#" + hex).colpickHide().next("div").css("background-color", "#" + hex);
				},
				onBeforeShow: function(colpick) {
				},
				onShow: function(colpick) {
					$(colpick).fadeIn("fast");
				},
				onHide: function(colpick) {
				}
			}).on({
				keyup: function() {
					$(this).colpickSetColor("#" + this.value);
				},
				change: function() {
					$(this).colpickSetColor("#" + this.value);
				}
			});
			';
		}
		
		$html .= '<div class="color-div">';
		$html .= '<input type="text" data-kit="color" '.$attr.'></input><div style="background-color: '.$value.'"></div>';
		$html .= '</div>';
		
		return array($html, $js);
	}
	
	function date($attr, array $param=null) {
		static $pass;$html = null;$js = null;
		
		//id 防呆
		$id = explode_attr($attr, 'id', '"');
		if (empty($id)) {
			$id = uniqid();
			$attr = 'id="'.$id.'" '.$attr;
		}
		
		if (!$pass) {
			$pass = true;
			$this->set_js(URL_ROOT.'js/jquery-ui/i18n-datepicker/jquery.ui.datepicker-zh-TW.js', 'src');
		}
		$html.= '<input type="text" readonly="readonly" size="12" '.$attr.'>&emsp;<input type="button" value="'._('Clear').'">';
		
		//customize
		$dateFormat = isset($param['dateFormat'])? $param['dateFormat'] : 'yy-mm-dd';
		if (isset($param['maxDate'])) {
			if (!empty($param['maxDate'])) {
				$tmp1 = explode(',', $param['maxDate']);
				if (!is_null($tmp1[1])) {
					$tmp1[1] -= 1;//js 的 month 是從 0 開始
					$param['maxDate'] = implode($tmp1, ',');
				}
			}
			$maxDate = 'maxDate: new Date('.$param['maxDate'].'),';
		} else {
			$maxDate = null;
		}
		
		$js .= '
		$(function(){$("#'.$id.'").datepicker({
				'.$maxDate.'
				dateFormat: "'.$dateFormat.'", changeYear: true, changeMonth: true, showButtonPanel: true
		}).next("input:button").on("click", function(){$(this).prev("input:text").val("");});});';
	
		return array($html, $js);
	}
	
	function datetime($attr, $param=array()) {
		static $pass;$html = null;$js = null;
		
		//id 防呆
		$id = explode_attr($attr, 'id', '"');
		if (empty($id)) {
			$id = uniqid();
			$attr = 'id="'.$id.'" '.$attr;
		}
		
		if (!$pass) {
			$pass = true;
			$this->set_css(URL_ROOT.'js/jquery-timepicker/jquery-ui-timepicker-addon.css', 'href');
			$this->set_js(URL_ROOT.'js/jquery-ui/i18n-datepicker/jquery.ui.datepicker-zh-TW.js', 'src');
			$this->set_js(URL_ROOT.'js/jquery-timepicker/jquery-ui-timepicker-addon.js', 'src');
			$this->set_js(URL_ROOT.'js/jquery-timepicker/localization/jquery-ui-timepicker-zh-TW.js', 'src');
		}
		
		//datetimepicker 處理 0000-00-00 00:00:00 會出錯
		$attr = str_replace('0000-00-00 00:00:00', '', $attr);
		
		$html.= '<input type="text" readonly="readonly" size="19" '.$attr.'>&emsp;<input type="button" value="'._('Clear').'">';
	
		if (isset($param['minDateTime'])) {
			if (!empty($param['minDateTime'])) {
				$tmp1 = explode(',', $param['minDateTime']);
				if (!is_null($tmp1[1])) {
					$tmp1[1] -= 1;//js 的 month 是從 0 開始
					$param['minDateTime'] = implode($tmp1, ',');
				}
			}
			$minDateTime = 'minDateTime: new Date('.$param['minDateTime'].'),';
		} else {
			$minDateTime = null;
		}
		if (isset($param['maxDateTime'])) {
			if (!empty($param['maxDateTime'])) {
				$tmp1 = explode(',', $param['maxDateTime']);
				if (!is_null($tmp1[1])) {
					$tmp1[1] -= 1;//js 的 month 是從 0 開始
					$param['maxDateTime'] = implode($tmp1, ',');
				}
			}
			$maxDateTime = 'maxDateTime: new Date('.$param['maxDateTime'].'),';
		} else {
			$maxDateTime = null;
		}
		$js .= '
		$(function(){
			$("#'.$id.'").datetimepicker({
				'.$minDateTime.'
				'.$maxDateTime.'
				dateFormat: "yy-mm-dd",
				timeFormat: "HH:mm:ss",
				changeYear: true,
				changeMonth: true,
				showButtonPanel: true
			}).next("input:button").on("click", function(){
				$(this).prev("input:text").val("");
			});
		});
		';
	
		return array($html, $js);
	}
	
	function dynamictable($function, $attr, $array=array()) {
		static $pass1;$return = null;$js = null;$uniqid = uniqid();
	
		if (!$pass1) {
			$pass1 = true;
			self::$css .= '
			.dynamictable-area {
			}
			.dynamictable-button-add {
			    background-color: #2894FF;
			    border-radius: 4px;
				color: #FFFFFF;
			    cursor: pointer;
			    font-size: 15px;
				padding: 5px 15px;
				transition: all 0.3s ease 0s;
				width: 27px;
			}
			.dynamictable-button-add:hover {
				background-color: #0072E3;
			}
			.dynamictable-button-reduce {
			    background-color: #FF0000;
			    border-radius: 4px;
			    color: #FFFFFF;
			    cursor: pointer;
			    float: left;
			    font-size: 15px;
			    padding: 5px 15px;
				transition: all 0.3s ease 0s;
			}
			.dynamictable-button-reduce:hover {
				background-color: #EA0000;
			}
			.dynamictable-panel {
				float: left;
				margin: 10px;
				width: inherit;
			}
			.dynamictable-panel ul {
				float: left;
    			margin: 0 5px;
			}
			.dynamictable-index {
			}
			.dynamictable-index-span {
			}
			.dynamictable-html {
			}
			';
		}
	
		//id 防呆, 必須要替換為 __FUNCTION__, 才能接續下方的 js 作業
		$id = explode_attr($attr, 'id', '"');
		if (empty($id)) {
			$attr = 'id="'.__FUNCTION__.'" '.$attr;
		} else {
			$attr = str_replace($id, __FUNCTION__, $attr);
		}
	
		$return .= '<div class="'.__FUNCTION__.'-button-add" onclick="'.__FUNCTION__.'_'.$uniqid.'_add()">'._('Add').'</div>';
		$return .= '<div id="'.__FUNCTION__.'-'.$uniqid.'-area" class="'.__FUNCTION__.'-area">';
	
		//有預設值的話在這處理, 不採由 js set value(例如 image 的 value, 就要再取出來往 a、img 放), 這樣的話在往後開發會增添制約, 只是要注意這裡會有兩處邏輯要處理
		if (!empty($array)) {
			foreach ($array as $v1) {
				list($function_html, $function_js) = is_array($v1)? $this->$function($attr, $v1) : $this->$function($attr.' value="'.$v1.'"');
	
				$tmp_id = uniqid();
				$function_html = str_replace(__FUNCTION__, $function.'-'.$tmp_id, $function_html);
				$function_js = str_replace(__FUNCTION__, $function.'-'.$tmp_id, $function_js);
	
				$return .= '<div id="'.__FUNCTION__.'-panel-'.$tmp_id.'" class="'.__FUNCTION__.'-panel">';
				$return .= '<ul class="'.__FUNCTION__.'-index"></ul>';
				$return .= '<ul class="'.__FUNCTION__.'-button-reduce" onclick="'.__FUNCTION__.'_'.$uniqid.'_reduce(this)">'._('Delete').'</ul>';
				$return .= '<ul class="'.__FUNCTION__.'-html">'.$function_html.'</ul>';
				$return .= '</div>';
	
				$js .= $function_js;
			}
		}
	
		$return .= '</div>';
	
		// js template
		list($function_html, $function_js) = $this->$function($attr);
		
		//防呆: php 換行符號輸出到 js 時會有錯誤訊息(unterminated string literal)
		$function_html = trim(preg_replace('/\s+/', ' ', $function_html));
		$function_js = trim(preg_replace('/\s+/', ' ', $function_js));
		
		$js .= '
		$(function(){
			'.__FUNCTION__.'_'.$uniqid.'_init();
		});
		function '.__FUNCTION__.'_'.$uniqid.'_init() {
			$("#'.__FUNCTION__.'-'.$uniqid.'-area .'.__FUNCTION__.'-panel").each(function(index) {
				$(this).find(".'.__FUNCTION__.'-index").text(index + 1);
			});
		}
		function '.__FUNCTION__.'_'.$uniqid.'_add() {
			var tmp_html = \''.$function_html.'\';
			var tmp_js = \''.$function_js.'\';
			var tmp_id = \'\' + Math.floor((1 + Math.random()) * 0x10000).toString(16).substring(1) + \'\';
			tmp_html = tmp_html.replace(/'.__FUNCTION__.'/g, "'.$function.'-" + tmp_id);
			tmp_js = tmp_js.replace(/'.__FUNCTION__.'/g, "'.$function.'-" + tmp_id);
			var html = \'<div id="'.__FUNCTION__.'-panel-\' + tmp_id + \'" class="'.__FUNCTION__.'-panel">\';
			html += \'<ul class="'.__FUNCTION__.'-index"></ul>\';
			html += \'<ul class="'.__FUNCTION__.'-button-reduce" onclick="'.__FUNCTION__.'_'.$uniqid.'_reduce(this)">'._('Delete').'</ul>\';
			html += \'<ul class="'.__FUNCTION__.'-html">\' + tmp_html + \'</ul>\';
			html += \'</div>\';

			//jquery 的 append 無法做 js 的寫入 dom, 它會寫入後執行再被刪除, 推測是要避免權限被拒, 參考 http://stackoverflow.com/questions/610995/cant-append-script-element
			var script = document.createElement("script");
			script.type = "text/javascript";
			script.text = "" + tmp_js + "";
			$("#'.__FUNCTION__.'-'.$uniqid.'-area").append(html).ready(function(){
				document.getElementById("'.__FUNCTION__.'-panel-" + tmp_id).appendChild(script);
				'.__FUNCTION__.'_'.$uniqid.'_init();
			});
		}
		function '.__FUNCTION__.'_'.$uniqid.'_reduce(obj) {
			$(obj).parent().remove().ready(function(){
				'.__FUNCTION__.'_'.$uniqid.'_init();
			});
		}
		$("#'.__FUNCTION__.'-'.$uniqid.'-area").sortable({
			placeholder: "'.__FUNCTION__.'-panel",
			update: function(event, ui) {
				'.__FUNCTION__.'_'.$uniqid.'_init();
			}
		});
		';
	
		return array($return, $js);
	}
	
	function email(array $attr) {
		return array('<input type="email" '.array2htmlattr($attr).'>', null);
	}
	
	function form($attr, $content) {
		static $pass;$html = null;
		if (!$pass) {
			$pass = true;
			$this->jbox();
			self::$js .= '
				function formerror(content){return new jBox("Modal",{delayOpen:200,title:"<span class=\"helper-valign-middle\"></span><img class=\"valign-middle\" src=\"'.static_file('images/error.png').'\">"}).setContent(content).open();}
				function formerror_v2(r){return new jBox("Modal",{delayOpen:200,title:"<span class=\"helper-valign-middle\"></span><img class=\"valign-middle\" src=\"'.static_file('images/error.png').'\">"}).setContent(r.message).open();}
				function formnotice(r){return new jBox("Modal",{delayOpen:200,title:"<span class=\"helper-valign-middle\"></span><img class=\"valign-middle\" src=\"'.static_file('images/info.png').'\">",onCloseComplete:function(){if(r.redirect){location.href=r.redirect;}}}).setContent(r.message).open();}
				function formsuccess(content){return new jBox("Modal",{delayOpen:200,title:"<span class=\"helper-valign-middle\"></span><img class=\"valign-middle\" src=\"'.static_file('images/success.png').'\">"}).setContent(content).open();}
				function formconfirm(r){var formConfirm=new jBox("Confirm",{cancelButton:\''._('No').'\',confirm:function(){location.href=r.redirect;},confirmButton:\''._('Yes').'\',delayOpen:200,onCloseComplete:function(){formConfirm.destroy();}}).setContent(r.message).open();}';
		}
		$html = '<form '.$attr.'>'.$content.'</form>';
	
		return [$html, null];
	}
	
	function formtable($formattr=null, $tableattr=null, $column=array(), $extra=null) {
		static $pass;$html = null;$js = null;
		if (!$pass) {
			$pass = true;
			self::$css .= '
			.formtable-tr-head {
			}
			.formtable-tr-body {
			}
			.formtable-tr-foot {
			}
			';
		}
	
		$html = '<form '.$formattr.'><table '.$tableattr.'>';
		if (!empty($column)) {
			foreach ($column as $k1 => $v1) {
				$trattr = isset($v1['trattr'])? $v1['trattr'] : null;
				$tdkeyattr = isset($v1['tdkeyattr'])? $v1['tdkeyattr'] : null;
				$tdvalueattr = isset($v1['tdvalueattr'])? $v1['tdvalueattr'] : null;
				if ($k1 == 0) {
					$tr_class = 'class="formtable-tr-head"';
				} elseif ($k1 == count($column) - 1) {
					$tr_class = 'class="formtable-tr-foot"';
				} else {
					$tr_class = 'class="formtable-tr-body"';
				}
				$html .= '<tr '.$tr_class.' '.$trattr.'>';
				$html .= '<td '.$tdkeyattr.' style="width:15%;">'.$v1['key'].'</td>';
				$html .= '<td '.$tdvalueattr.'>'.$v1['value'].'</td>';
				$html .= '</tr>';
			}
		}
		$html .= '</table>'.$extra.'</form>';
	
		return array($html, $js);
	}
	
	function get_css() {
		$return = null;
		if (!empty(self::$css_src)) {
			$css_src = self::$css_src;
			if (array_key_exists('jquery_ui', $css_src)) {
				$tmp1 = array();
				$tmp1['jquery_ui'] = $css_src['jquery_ui'];
				$return .= '<link type="text/css" href="'.$css_src['jquery_ui'].'" rel="stylesheet" />';
				$css_src = array_diff($css_src, $tmp1);
			}
			foreach ($css_src as $v1) {
				$return .= '<link type="text/css" href="'.$v1.'" rel="stylesheet" />';
			}
		}
		if (!empty(self::$css)) {
			$return .= '<style type="text/css">'.self::$css.'</style>';
		}
	
		return $return;
	}
	
	function get_js() {
		$js_src = null;
		$js = null;
		if (!empty(self::$js_src)) {
			$a_js_src = self::$js_src;
			foreach (array('jquery', 'jquery_ui') as $v1) {
				if (array_key_exists($v1, $a_js_src)) {
					$tmp1 = array();
					$tmp1[$v1] = $a_js_src[$v1];
					$js_src .= '<script type="text/javascript" src="'.$a_js_src[$v1].'"></script>';
					$a_js_src = array_diff($a_js_src, $tmp1);
				}
			}
			foreach ($a_js_src as $v1) {
				$js_src .= '<script type="text/javascript" src="'.$v1.'"></script>';
			}
		}
		if (!empty(self::$js)) {
			$js .= '<script type="text/javascript">'.self::$js.'</script>';
		}
	
		return array($js_src, $js);
	}
	
	function grid($id=null) {
		static $pass;$html = null;
	
		//防呆: id
		if (empty($id)) $id = 'grid';
	
		if (!$pass) {
			$pass = true;
			$this->jbox();
			$this->set_css(URL_STATIC_FILE.M_PACKAGE.'/'.SITE_LANG.'/js/telerik.kendoui.professional.2016.1.112.trial/styles/kendo.common.min.css', 'href');
			$this->set_css(URL_STATIC_FILE.M_PACKAGE.'/'.SITE_LANG.'/js/telerik.kendoui.professional.2016.1.112.trial/styles/kendo.silver.min.css', 'href');
			$this->set_css(URL_STATIC_FILE.M_PACKAGE.'/'.SITE_LANG.'/js/telerik.kendoui.professional.2016.1.112.trial/styles/kendo.dataviz.min.css', 'href');
			$this->set_css(URL_STATIC_FILE.M_PACKAGE.'/'.SITE_LANG.'/js/telerik.kendoui.professional.2016.1.112.trial/styles/kendo.dataviz.silver.min.css', 'href');
			$this->set_js(URL_STATIC_FILE.M_PACKAGE.'/'.SITE_LANG.'/js/telerik.kendoui.professional.2016.1.112.trial/js/jszip.min.js', 'src');
			$this->set_js(URL_STATIC_FILE.M_PACKAGE.'/'.SITE_LANG.'/js/telerik.kendoui.professional.2016.1.112.trial/js/kendo.all.min.js', 'src');
			$this->set_js(URL_STATIC_FILE.M_PACKAGE.'/'.SITE_LANG.'/js/telerik.kendoui.professional.2016.1.112.trial/js/cultures/kendo.culture.zh-TW.min.js', 'src');
			$this->set_js(URL_STATIC_FILE.M_PACKAGE.'/'.SITE_LANG.'/js/telerik.kendoui.professional.2016.1.112.trial/js/messages/kendo.messages.zh-TW.min.js', 'src');
			
			/**
			 * Lion 2014-12-23: gridheight 最後減的 20 為 #content 的 padding, 2 為 grid 的 border
			 */
			self::$js .= '
			kendo.culture("zh-TW");var gridheight=$(window).height()-$("#head").outerHeight(true)-$("#nav").outerHeight(true)-20-2,gridpageSize=20,gridpageSizes=[20,50,100],gridModal=new jBox("Modal",{delayOpen:200});
			function griderror(content){return new jBox("Modal",{delayOpen:200,title:"<span class=\"helper-valign-middle\"></span><img class=\"valign-middle\" src=\"'.static_file('images/error.png').'\">"}).setContent(content).open();}function gridinfo(content){return new jBox("Modal",{delayOpen:200,title:"<span class=\"helper-valign-middle\"></span><img class=\"valign-middle\" src=\"'.static_file('images/info.png').'\">"}).setContent(content).open();}function gridsuccess(content){return new jBox("Modal",{delayOpen:200,title:"<span class=\"helper-valign-middle\"></span><img class=\"valign-middle\" src=\"'.static_file('images/success.png').'\">"}).setContent(content).open();}
			function gridnotice(content){return new jBox("Notice",{attributes:{x:"right",y:"bottom"},autoClose:3000,content:content});}
			function grid_toolbar_add(selector,url){top.location.href=url;}function grid_toolbar_edit(selector,url,param){var grid=$(selector).data("kendoGrid"),data=grid.dataItem(grid.select());if(data===null){gridinfo("Please select a row.");}else{var qs="";$.each(param,function(k1,v1){if(data.hasOwnProperty(v1)){qs+="&"+v1+"="+encodeURIComponent(data[v1]);}});top.location.href=url+qs;}}
			function grid_toolbar_delete(selector,url,param){var grid=$(selector).data("kendoGrid"),data=grid.dataItem(grid.select());if(data===null){gridinfo("Please select a row.");}else{var obj={};$.each(param,function(k1,v1){if(data.hasOwnProperty(v1)){obj[v1]=data[v1];}});var gridConfirm=new jBox("Confirm",{cancelButton:\''._('No').'\',confirm:function(){$.ajax({data:obj,type:"POST",url:url}).done(function(r){r=$.parseJSON(r);if(r.result==1){grid.removeRow(grid.select());gridsuccess(r.message);}else{griderror(r.message);}});},confirmButton:\''._('Yes').'\',delayOpen:200,onCloseComplete:function(){gridConfirm.destroy();}}).setContent("'._('Are you sure to delete it?').'").open();}}
			function grid_toolbar_excel(selector,url,param){var grid=$(selector).data("kendoGrid"),data=grid.dataItem(grid.select());if(data===null){gridinfo("Please select a row.");}else{var qs=[];$.each(param,function(k0,v0){if(data.hasOwnProperty(v0)){qs.push(v0+"="+encodeURIComponent(data[v0]));}});top.location.href=qs.length?url+"?"+qs.join("&"):url;}}
			function grid_toolbar_download(selector,url,param){var grid=$(selector).data("kendoGrid"),data=grid.dataItem(grid.select());if(data===null){gridinfo("Please select a row.");}else{var qs=[];$.each(param,function(k0,v0){if(data.hasOwnProperty(v0)){qs.push(v0+"="+encodeURIComponent(data[v0]));}});if(qs.length)url+="?"+qs.join("&");$.post(url,{ready:true},function(r){r=$.parseJSON(r);if(r.result){top.location.href=url;}else{griderror(r.message);}});}}';
		}
		$html .= '<div id="'.$id.'"></div>';
		
		return [$html, null];
	}
	
	function hidden($attr, $text=null) {
		return ['<input type="hidden" '.$attr.'>'.$text, null];
	}
	
	function image($attr, array $allow_extension=null, $width=null, $height=null) {
		static $pass;$html = null;$js = null;
		
		if ($allow_extension === null) $allow_extension = ['image/*'];
		
		//id 防呆
		$uniqid = explode_attr($attr, 'id', '"');
		if (empty($uniqid)) {
			$uniqid = uniqid();
			$attr = 'id="'.$uniqid.'" '.$attr;
		}
	
		$href = $src = explode_attr($attr, 'value', '"');
		if (!$pass) {
			$pass = true;
			self::$css .= '
				.image-div{
					height: 100px;
					width: 1015px;
				}
				.image-div div{
					margin: 0 5px 0 0;
				}
				.image-a {
				    float: left;
				    position: relative;
				}
				.image-editor {
				    background-color: #FFA042;
				    border-radius: 4px;
				    color: #FFFFFF;
				    cursor: pointer;
				    float: left;
				    font-size: 15px;
				    padding: 5px 15px;
				    position: relative;
				    top: 35%;
				    transition: all 0.3s ease 0s;
				}
				.image-editor:hover {
					background-color: #EA7500;
				}';
				
			//^$this->set_js('https://dme0ih8comzn4.cloudfront.net/js/feather.js', 'src');
			$this->set_js(URL_ROOT.'js/feather.js', 'src');
			
			//參照說明 3.5.
			$a_language = [
					'en_US'=>'en',
					'zh_TW'=>'zh_HANT',
			];
			self::$js .= '
				var featherEditor = new Aviary.Feather({
					apiKey: "'.\Core::settings('AVIARY_FEATHER_KEY').'",
					language: "'.$a_language[\Core\Lang::get()].'",
					onSave: function(imageID, newURL) {
						aID = imageID.replace(/-img/g, "-a");
						$.post("'.\Core::controller()->url('upload', 'aviary').'", {
							href: document.getElementById(aID).href,
							url: newURL,
						}, function(r) {
							r = $.parseJSON(r);
							if (r.result) {
								document.getElementById(aID).href = r.data.href;
								document.getElementById(imageID).src = r.data.src;
							} else {
								formerror_v2(r);
							}
						});
					},
					onError: function(errorObj) {		
						formerror_v2(errorObj);
					}
				});
				$(document).on("click", ".image-editor", function(){
					var id = $(this).attr("data-id"),
						$a = $("a[id="+ id +"-a]");
					featherEditor.launch({image: id + "-img", url: $a.prop("href")});
				});
				$(".image-a").draggable({helper: "clone"});';
			
			list($html_imagebox, $js_imagebox) = $this->imagebox('.image-a a');
			$html .= $html_imagebox;
			self::$js .= $js_imagebox;
		}
		
		$image_url = null;
		$image_thumbnail_url = null;
		if (!empty($src)) {
			$Image = new \Core\Image;
			$fileinfo = fileinfo($src);
			$image_url = $fileinfo['url'];
			$image_thumbnail_url = fileinfo($Image->setImage($fileinfo['path'])->setSize()->save())['url'];
		}
	
		$html .= '<div class="image-div">';
		$html .= '<div class="image-a">';
		$html .= '<a id="'.$uniqid.'-a" title="" href="'.$image_url.'"><img id="'.$uniqid.'-img" border="0" data-height="'.$height.'" data-original="'.$image_thumbnail_url.'" data-width="'.$width.'"></a>';
		$html .= '</div>';
		$html .= '<div id="image-editor-'.$uniqid.'" class="image-editor" data-id="'.$uniqid.'">'._('Image Edit').'</div>';
		
		list($html_upload, $js_upload) = $this->upload($attr, $allow_extension);
		$html .= $html_upload;
		$js .= $js_upload;
		
		$html .= '</div>';
	
		return [$html, $js];
	}
	
	function imagebox($selector=null) {
		static $pass;$html = null;$js = null;
		if (!$pass) {
			$pass = true;
			$this->set_css(URL_ROOT.'js/Gallery-2.15.2/css/blueimp-gallery.min.css', 'href');
			$this->set_js(URL_ROOT.'js/Gallery-2.15.2/js/blueimp-gallery.min.js', 'src');
			self::$js .= '
			$(function(){
				$("body").append(\'<div id="blueimp-gallery" class="blueimp-gallery blueimp-gallery-controls"><div class="slides"></div><h3 class="title"></h3><a class="prev">‹</a><a class="next">›</a><a class="close">×</a><a class="play-pause"></a><ol class="indicator"></ol></div>\');
			});
			';
		}
		if (!empty($selector)) {
			$js .= '
			$(document).on("click", "'.$selector.'", function(event){
			    event = event || window.event;
			    var target = event.target || event.srcElement,
			        link = target.src ? target.parentNode : target;
			    blueimp.Gallery($("'.$selector.'"), {index: link, event: event});
			});
			';
		}
		
		return array($html, $js);
	}
	
	function image_combine($attr) {
		static $pass;$html = null;$js = null;
	
		//防呆: id
		$uniqid = explode_attr($attr, 'id', '"');
		if (empty($uniqid)) {
			$uniqid = uniqid(__FUNCTION__);
			$attr = 'id="'.$uniqid.'" '.$attr;
		}
	
		$href = $src = explode_attr($attr, 'value', '"');
	
		if (!$pass) {
			$pass = true;
			self::$css .= '
			.'.__FUNCTION__.'-area-background {
			    border: 1px dashed #000000;
				float: left;
			    height: 100px;
			    width: 100px;
			}
			.'.__FUNCTION__.'-area-background-text {
				left: 17px;
			    position: relative;
			    top: 41px;
			}
			.'.__FUNCTION__.'-area-foreground {
			    border: 1px dashed #000000;
				float: left;
			    height: 100px;
			    width: 100px;
			}
			.'.__FUNCTION__.'-area-foreground-text {
				left: 17px;
			    position: relative;
			    top: 41px;
			}
			.'.__FUNCTION__.'-div{
				height: 100px;
				padding: 10px 0;
				width: 900px;
			}
			.'.__FUNCTION__.'-div div{
				margin: 0 5px 0 0;
			}
			.'.__FUNCTION__.'-a {
			    float: left;
			    position: relative;
			}
			.'.__FUNCTION__.'-input {
			    float: left;
			    position: relative;
			    top: 40%;
			}
			.button-dialog {
			    background-color: #5B5B5B;
			    border-radius: 4px;
			    color: #FFFFFF;
			    cursor: pointer;
			    float: left;
			    font-size: 15px;
			    padding: 5px 15px;
			    position: relative;
			    top: 35%;
			    transition: all 0.3s ease 0s;
			}
			.button-dialog:hover {
				background-color: #272727;
			}
			';
	
			list($html_imagebox, $js_imagebox) = $this->imagebox('.'.__FUNCTION__.'-a a');
			$html .= $html_imagebox;
			$js .= $js_imagebox;
	
			$js .= '
			$(".'.__FUNCTION__.'-area-background").droppable({
				drop: function (event, ui) {
					$(this).find(":hidden").val(ui.draggable.parent().find("div input").prop("id"));
					var url = ui.draggable.find("img").prop("src");
					$(this).css({
						background: "url(" + url + ") no-repeat scroll 0 0 rgba(0, 0, 0, 0)"
					});
				}
			});
			$(".'.__FUNCTION__.'-area-foreground").droppable({
				drop: function (event, ui) {
					$(this).find(":hidden").val(ui.draggable.parent().find("div input").prop("id"));
					var url = ui.draggable.find("img").prop("src");
					$(this).css({
						background: "url(" + url + ") no-repeat scroll 0 0 rgba(0, 0, 0, 0)"
					});
				}
			});
			';
		}
	
		$url_upload_src = empty($src)? null : URL_UPLOAD.getimageresize($src);
	
		$html .= '<div id="'.$uniqid.'-dialog">';
		$html .= '<div id="'.$uniqid.'-dialog-background"></div>';
		$html .= '<div id="'.$uniqid.'-dialog-foreground"></div>';
		$html .= '</div>';
		$html .= '<div class="'.__FUNCTION__.'-div">';
		$html .= '<div id="'.$uniqid.'-area-background" class="'.__FUNCTION__.'-area-background"><li class="'.__FUNCTION__.'-area-background-text">'._('Background').'</li><input type="hidden" id="'.$uniqid.'-area-background-hidden"></div>';
		$html .= '<div id="'.$uniqid.'-area-foreground" class="'.__FUNCTION__.'-area-foreground"><li class="'.__FUNCTION__.'-area-foreground-text">'._('Foreground').'</li><input type="hidden" id="'.$uniqid.'-area-foreground-hidden"></div>';
		$html .= '<div class="'.__FUNCTION__.'-a">';
		$html .= '<a id="'.$uniqid.'-a" title="" href="'.URL_UPLOAD.$href.'"><img id="'.$uniqid.'-img" border="0" data-original="'.$url_upload_src.'"></a>';
		$html .= '</div>';
		$html .= '<div class="'.__FUNCTION__.'-input"><input type="text" '.$attr.' size="64" readonly></div>';
		$html .= '<div class="button-dialog">'._('Dialog').'</div>';
	
		//參照說明 3.
		$js .= '$(".button-dialog").on("click", function(){$("#'.$uniqid.'-dialog").dialog("open");});';
	
		$js .= '
		$(function(){
			var '.__FUNCTION__.'_area_background_hidden = $("#'.$uniqid.'-area-background-hidden");
			var '.__FUNCTION__.'_area_foreground_hidden = $("#'.$uniqid.'-area-foreground-hidden");
			$("#'.$uniqid.'-dialog").dialog({
				autoOpen: false,
				modal: true,
				height: 768,
				width: 1024,
				show: {
					effect: "fade",
					duration: 200
				},
				hide: {
					effect: "fade",
					duration: 200
				},
				buttons: {
					ok: function () {
						var obj = this;
						$.post("'.Core::controller()->url('upload', 'image_combine', array('class'=>M_CLASS)).'", {
							background: $("#" + '.__FUNCTION__.'_area_background_hidden.val()).val(),
							foreground: $("#" + '.__FUNCTION__.'_area_foreground_hidden.val()).val(),
							foreground_left: $("#'.$uniqid.'-dialog-foreground").css("left"),
							foreground_top: $("#'.$uniqid.'-dialog-foreground").css("top")
						}, function(r){
							r = $.parseJSON(r);
							alert(r.message);
							if (r.result) {
								$("#'.$uniqid.'-a").prop({title: r.data.file_name, href: r.data.file_url});
								$("#'.$uniqid.'-img").prop("src", r.data.file_thumbnail_url);
								$("#'.$uniqid.'").prop("value", r.data.file_folder);
							}
							$(obj).dialog("close");
						});
					}
				},
				open: function (event, ui) {
					var image_background_url = "'.URL_UPLOAD.'" + $("#" + '.__FUNCTION__.'_area_background_hidden.val()).prop("value");
					var img1 = new Image();
					img1.onload = function() {
						$("#'.$uniqid.'-dialog-background").css({
							background: "url(" + image_background_url + ") no-repeat scroll 0 0 rgba(0, 0, 0, 0)",
							width: this.width,
							height: this.height,
							border: "1px dashed black",
							position: "absolute"
						});
					}
					img1.src = image_background_url;

					var image_foreground_url = "'.URL_UPLOAD.'" + $("#" + '.__FUNCTION__.'_area_foreground_hidden.val()).prop("value");
					var img2 = new Image();
					img2.onload = function() {
						$("#'.$uniqid.'-dialog-foreground").css({
							background: "url(" + image_foreground_url + ") no-repeat scroll 0 0 rgba(0, 0, 0, 0)",
							width: this.width,
							height: this.height,
							cursor: "move",
							position: "relative"
						});
					}
					img2.src = image_foreground_url;

					$("#'.$uniqid.'-dialog-foreground").draggable({containment: "#'.$uniqid.'-dialog-background"});
				},
				beforeClose: function (event, ui) {
				},
				close: function (event, ui) {
				}
			});
		});
		';
	
		return array($html, $js);
	}
	
	function img($attr) {
		static $pass;$html = null;$js = null;static $Image;
		
		//防呆: id
		$uniqid = explode_attr($attr, 'id', '"');
		if (empty($uniqid)) {
			$uniqid = uniqid(__FUNCTION__);
			$attr = 'id="'.$uniqid.'" '.$attr;
		}
		
		$href = $src = explode_attr($attr, 'value', '"');
		
		if (!$pass) {
			$pass = true;
			
			list($html_imagebox, $js_imagebox) = $this->browseKit(['selector'=>'.'.__FUNCTION__.'-a']);
			$html .= $html_imagebox;
			$js .= $js_imagebox;
		}
		
		$size = null;
		$url_upload_src = null;
		if (is_image(PATH_UPLOAD.$src)) {
			if (!$Image) $Image = new \Core\Image;
			$i_image = $Image->setImage(PATH_UPLOAD.$src);
			$size = $i_image->getWidth().'x'.$i_image->getHeight();
			$url_upload_src = fileinfo($i_image->setSize()->save())['url'];
		}
		
		/**
		 * data-original: lazyload 用
		 * data-size: browseKit 用
		 */
		$html .= '
			<a id="'.$uniqid.'-a" class="'.__FUNCTION__.'-a" href="'.URL_UPLOAD.$href.'" data-size="'.$size.'">
				<img id="'.$uniqid.'-img" data-original="'.$url_upload_src.'">
			</a>';
		
		return [$html, $js];
	}
	
	function input($attr) {
		return array('<input '.$attr.'>', null);
	}
	
	function jbox() {
		static $pass;
		if (!$pass) {
			$pass = true;
			$this->set_css(URL_ROOT.'js/jBox-0.3.2/Source/jBox.css', 'href');
			$this->set_js(URL_ROOT.'js/jBox-0.3.2/Source/jBox.min.js', 'src');
		}
	}
	
	/**
	 * popup 訊息提示
	 * <p>2014-09-26: 參照 http://www.myjqueryplugins.com/jquery-plugin/jnotify</p>
	 * [Options]
	 * <autoHide> Boolean Default : true - jNotify closed after TimeShown ms or by clicking on it
	 * <clickOverlay> Boolean Default : false - If false, disables closing jNotify by clicking on the background overlay. 
	 * <MinWidth> Integer Default : 200 - In pixel, the min-width css property of the boxes.
	 * <TimeShown> Integer Default : 1500 - In ms, time of the boxes appearances.
	 * <ShowTimeEffect> Integer Default : 200 - In ms, duration of the Show effect
	 * <HideTimeEffect> Integer Default : 200 - In ms, duration of the Hide effect
	 * <LongTrip> Integer Default : 15 - Length of the move effect ('top' and 'bottom' verticals positions only)
	 * <HorizontalPosition> String Default : right - Horizontal position. Can be set to 'left', 'center', 'right'
	 * <VerticalPosition> String Default : top - Vertical position. Can be set to 'top', 'center', 'bottom'.
	 * <ShowOverlay> Boolean Default : true - If true, a background overlay appears behind the jNotify boxes
	 * <ColorOverlay> String Default : #000 - Color of the overlay background (only Hex. color code)
	 * <OpacityOverlay> Integer Default : 0.3 - Opacity CSS property of the overlay background. From 0 to 1 max.
	 * 
	 * [Methods]
	 * <onCompleted> Function Returns : null - Callback that fires right after jNotify content is displayed.
	 * <onClosed> Function Returns : null - Callback taht fires once jNotify is closed 
	 */
	function jNotify() {
		static $pass;
		
		if (!$pass) {
			$pass = true;
			$this->set_css(URL_ROOT.'js/jNotify-master/jquery/jNotify.jquery.css', 'href');
			$this->set_js(URL_ROOT.'js/jNotify-master/jquery/jNotify.jquery.min.js', 'src');
		}
	}
	
	function jqgrid($id=null) {
		static $pass;$html = null;
		
		//防呆: id
		if (empty($id)) {
			$id = 'jqgrid';
		}
		
		if (!$pass) {
			$pass = true;
			$this->set_css(URL_ROOT.'js/jquery.jqGrid-4.6.0/css/ui.jqgrid.css', 'href');
			$this->set_js(URL_ROOT.'js/jquery.jqGrid-4.6.0/js/jquery.jqGrid.min.js', 'src');
			$this->set_js(URL_ROOT.'js/jquery.jqGrid-4.6.0/js/i18n/grid.locale-tw.js', 'src');
		}
		$html.= '<table id="'.$id.'"></table><div id="p'.$id.'"></div>';
	
		return [$html, null];
	}
	
	function keyvalueremark($attr, $array=array()) {
		static $pass;$html = null;$js = null;
		if (!$pass) {
			$pass = true;
			self::$js .= 'var keyvalueremark = new Array("key", "value", "remark");';
		}
	
		$id = explode_attr($attr, 'id', '"');
		$name = explode_attr($attr, 'name', '"');
		
		$id_key = $id.'_key';
		$id_value = $id.'_value';
		$id_remark = $id.'_remark';
		$tmp1 = strpos($name, '[');
		if ($tmp1 === false) {
			$name_key = $name.'_key';
			$name_value = $name.'_value';
			$name_remark = $name.'_remark';
		} else {
			$tmp2 = substr($name, 0, $tmp1);
			$tmp3 = substr($name, $tmp1);
			$name_key = $tmp2.'_key'.$tmp3;
			$name_value = $tmp2.'_value'.$tmp3;
			$name_remark = $tmp2.'_remark'.$tmp3;
		}
		$tmp1 = array('id="'.$id.'"', 'name="'.$name.'"');
		$tmp3 = array('id="'.$id_key.'"', 'name="'.$name_key.'"');
		$tmp4 = array('id="'.$id_value.'"', 'name="'.$name_value.'"');
		$tmp5 = array('id="'.$id_remark.'"', 'name="'.$name_remark.'"');
		$attr_key = str_replace($tmp1, $tmp3, $attr);
		$attr_value = str_replace($tmp1, $tmp4, $attr);
		$attr_remark = str_replace($tmp1, $tmp5, $attr);
		if (isset($array['key'])) $attr_key .= ' value="'.$array['key'].'"';
		if (isset($array['value'])) $attr_value .= ' value="'.$array['value'].'"';
		if (isset($array['remark'])) $attr_remark .= ' value="'.$array['remark'].'"';
		$html .= _('Key').' : <input type="text" size="20" '.$attr_key.'>&emsp;';
		$html .= _('Value').' : <input type="text" size="50" '.$attr_value.'>&emsp;';
		$html .= _('Remark').' : <input type="text" size="60" '.$attr_remark.'>';
	
		return [$html, $js];
	}

	/**
	 * 暫緩顯示 img
	 * <p>v1.0 2014-08-19: 由於 tabs 切換之間會將已上傳的檔案又覆寫回去, 因此加上 load: function(){$(this).attr("data-original", "");} 清除</p>
	 */
	function lazyload() {
		static $pass;
		if (!$pass) {
			$pass = true;
			$this->set_js(URL_STATIC_FILE.M_PACKAGE.'/'.SITE_LANG.'/js/jquery_lazyload-1.9.5/jquery.lazyload.min.js', 'src');
			self::$js .= '$(function(){$("img").lazyload({effect: "fadeIn", load: function(){$(this).attr("data-original", "");}});});';
		}
	}
	
	function lightbox($selector=null) {
		static $pass;$html = null;$js = null;
		if (!$pass) {
			$pass = true;
			$this->set_js(URL_ROOT.'js/jquery-lightbox-0.5/js/jquery.lightbox-0.5.min.js', 'src');
			$this->set_css(URL_ROOT.'js/jquery-lightbox-0.5/css/jquery.lightbox-0.5.css', 'href');
		}
		if (!empty($selector)) $js .= '$("'.$selector.'").lightBox();';
	
		return [$html, $js];
	}

	function listtable(array $a_attr, $type, array $array) {
		static $pass;$html = null;$js = null;
		$width = isset($a_attr['width'])? $a_attr['width'] : 160;
		$height = isset($a_attr['height'])? $a_attr['height'] : 40;
		$col = isset($a_attr['col'])? $a_attr['col'] : 4;
		$width_align = ($width + 1) * $col.'px';//加 1 是 border 的 1px
		$width = $width.'px';
		$height = $height.'px';
		if (!$pass) {
			$pass = true;
			self::$css .= '
			.listtable-div-ul:before, .listtable-div-ul-li:before {
				content: "";
				display: inline-block;
				height: inherit;
				vertical-align: middle;
			}
			.listtable-div {
				border: 1px solid black;
			}
			.listtable-div-ul {
				border-bottom: 1px solid black;
			}
			.listtable-div-ul:last-child {
			    border-bottom: medium none;
			}
			.listtable-div-ul-li:last-child {
			    border-right: medium none;
			}
			.listtable-div-ul-li {
				border-right: 1px solid #BBBBBB;
			    float: left;
			}
			.listtable-div-ul-li-span {
				display: inline-block;
				vertical-align: middle;
				width: inherit;
			}
			';
		}

		$index = 0;
		$html .= '<div class="listtable-div" style="width: '.$width_align.'">';//內嵌 style 是因為要能隨設定變化
		foreach ($array as $k1 => $v1) {
			if ($index % $col == 0) {
				$html .= '<ul class="listtable-div-ul" style="height: '.$height.'">';//內嵌 style 是因為要能隨設定變化
			}
	
			switch ($type) {
				case 'img':
					list($tmp_html, $tmp_js) = $this->$type('value="'.$v1.'"');
					$tmp_html = ($k1 + 1).'&nbsp;'.$tmp_html;
					break;
					
				default:
					throw new Exception("[".__FUNCTION__."] Unknown case");
					break;
			}
			$html .= '<li class="listtable-div-ul-li" style="height: '.$height.'; width: '.$width.'"><span class="listtable-div-ul-li-span">'.$tmp_html.'</span></li>';//內嵌 style 是因為要能隨設定變化
			$js .= $tmp_js;
	
			if (($index + 1) % $col == 0 || ($index + 1) == count($array)) {
				$html .= '</ul>';
			}
			
			++$index;
		}
		$html .= '</div>';
	
		return array($html, $js);
	}
	
	/**
	 * 參照紀錄:
	 *     https://github.com/dimsemenov/Magnific-Popup/issues/53
	 * @return multitype:NULL
	 */
	function magnific_popup() {
		static $pass;$html = null;
		if (!$pass) {
			$pass = true;
			$this->set_css(URL_ROOT.'js/Magnific-Popup-master/dist/magnific-popup.css', 'href');
			$this->set_css(URL_ROOT.'js/Magnific-Popup-master/dist/magnific-popup-my-mfp-zoom-in.css', 'href');
			$this->set_js(URL_ROOT.'js/Magnific-Popup-master/dist/jquery.magnific-popup.min.js', 'src');
		}
		
		return [null, null];
	}
	
	function nivoslider() {
		static $pass;$html = null;
		if (!$pass) {
			$pass = true;
			$this->set_css(URL_ROOT.'js/nivo-slider-3.2/themes/default/default.css', 'href');
			$this->set_css(URL_ROOT.'js/nivo-slider-3.2/nivo-slider.css', 'href');
			$this->set_js(URL_ROOT.'js/nivo-slider-3.2/jquery.nivo.slider.pack.js', 'src');
		}
	
		return array($html, null);
	}
	
	function number($attr) {
		return ['<input type="number" '.$attr.'>', null];
	}
	
	function pace() {
		static $pass;$html = null;$js = null;
		if (!$pass) {
			$pass = true;
			$this->set_css(URL_ROOT.'js/pace-master-1.0.2/themes/blue/pace-theme-loading-bar.css', 'href');
			$this->set_js(URL_ROOT.'js/pace-master-1.0.2/pace.min.js', 'src');
		}
	}
	
	function panel(array $attr, $title=null, $content=null) {
		static $pass;$html = null;$js = null;
		
		if (!$pass) {
			$pass = true;
			
			$this->set_jquery();
			$this->set_css(URL_STATIC_FILE.M_PACKAGE.'/'.SITE_LANG.'/js/telerik.kendoui.professional.2016.1.112.trial/styles/kendo.common.min.css', 'href');
			$this->set_css(URL_STATIC_FILE.M_PACKAGE.'/'.SITE_LANG.'/js/telerik.kendoui.professional.2016.1.112.trial/styles/kendo.silver.min.css', 'href');
			$this->set_js(URL_STATIC_FILE.M_PACKAGE.'/'.SITE_LANG.'/js/telerik.kendoui.professional.2016.1.112.trial/js/kendo.all.min.js', 'src');
			
			self::$js .= '
			//exapand
			$("body").on("click", ".panel-title .k-i-arrowhead-s", function(e) {
				var t = $(e.target);
				t.removeClass("k-i-arrowhead-s").addClass("k-i-arrowhead-n");
				kendo.fx(t.closest(".panel").find(".panel-content")).expand("vertical").stop().play();
			});
			
			//collapse
			$("body").on("click", ".panel-title .k-i-arrowhead-n", function(e) {
				var t = $(e.target);
				t.removeClass("k-i-arrowhead-n").addClass("k-i-arrowhead-s");
				kendo.fx(t.closest(".panel").find(".panel-content")).expand("vertical").stop().reverse();
			});';
		}
		
		if (!isset($attr['id'])) $attr['id'] = uniqid();//id 防呆
		array_push_p($attr['class'], 'panel');
		
		$html .= '
		<div '.array2htmlattr($attr).'>
			<div class="panel-title">'.$title.'<span class="k-icon k-i-arrowhead-n"></span></div>
			<div class="panel-content">'.$content.'</div>
		</div>';
		
		$js .= '
		$(function(){
			$("body").kendoSortable({
				autoScroll: true,
				filter: "div[id=\"'.$attr['id'].'\"]",
				handler: ".panel-title",
				hint: function(e) {return e.clone().height(e.height()).width(e.width());},
				placeholder: function(e) {return e.clone().addClass("panel-placeholder");},
			});
		});';
		
		return [$html, $js];
	}
	
	function password(array $attr) {
		return ['<input type="password" '.array2htmlattr($attr).'>', null];
	}
	
	function radio($attr, $text=null) {
		static $pass;$html = null;$js = null;
		if (!$pass) {
			$pass = true;
			self::$css .= '
			.'.__FUNCTION__.'_label {
				cursor: pointer;
    			transition: all 0.3s ease 0s;
			}
			:checked + .'.__FUNCTION__.'_label {
				color: #3799FF;
			}
			';
		}
	
		//attr
		$id = explode_attr($attr, 'id', '"');
		if (empty($id)) {
			$id = uniqid();
			$attr = 'id="'.$id.'" '.$attr;
		}
	
		$html .= '<input type="radio" '.$attr.'><label class="'.__FUNCTION__.'_label" for="'.$id.'">&nbsp;'.$text.'</label>';
	
		return [$html, $js];
	}
	
	function radiotable($width, $height, $col, $array, $checkedvalue=null) {
		static $pass;$html = null;$js = null;
		if (!$pass) {
			$pass = true;
			self::$css .= '
			.radiotable_td {
				display: table-cell;
				height: '.$height.';
				vertical-align: middle;
				width: '.$width.';
			}
			';
		}
		$html .= '<div>';
		$index = 0;
		foreach ($array as $v1) {
			$uniqid = uniqid(__FUNCTION__);
			$name = isset($v1['name'])? 'name="'.$v1['name'].'"' : '';
			$value = isset($v1['value'])? 'value="'.$v1['value'].'"' : '';
			$text = isset($v1['text'])? $v1['text'] : '';
			$checked = ($checkedvalue == $v1['value'])? 'checked="checked"' : '';
			if ($index % $col == 0) {
				$html .= '<ul>';
			}
				
			list($radio_html, $radio_js) = $this->radio('id="'.$uniqid.'_input" '.$name.' '.$value.' '.$checked, $text);
				
			$html .= '<li id="'.$uniqid.'_td" class="radiotable_td">'.$radio_html.'</li>';
			$js .= $radio_js;
				
			if (($index + 1) % $col == 0 || ($index + 1) == count($array)) {
				$html .= '</ul>';
			}
			++$index;
		}
		$html .= '</div>';
	
		return array($html, $js);
	}
	
	function reload($attr) {
		return array('<input type="button" onclick="window.location.reload();return true;" '.$attr.'>', null);
	}
	
	function select($attr, $value, $selected_value=null) {
		$js = null;
		$html = '<select '.$attr.'>';
		$html .= '<option value=""></option>';
		foreach ($value as $k1 => $v1) {
			$selected = ($selected_value == $k1)? 'selected="selected"' : '';
			$html .= '<option value="'.$k1.'" '.$selected.'>'.$v1.'</option>';
		}
		$html .= '</select>';
		
		return array($html, $js);
	}
	
	/**
	 * select 套件
	 * <p>v1.0 2015-07-30:
	 *     allow_single_deselect 必須在第一個 option 的 text 為 blank 才作用，而套入 img 顯示時, 第一個 option 的 text 為 blank 的話會導致 img 顯示異常；
	 *     因兩者無法共存，故不使用 allow_single_deselect，並將第一個 option 的 value 為 blank，text 為 'Please select'；
	 *     multiple 下會能夠選到 'Please select'，所以在該情形排除。</p>
	 * @param array $attr
	 * @param array $option
	 * @param array/string $selected_value
	 * @param array/string $disabled_value
	 * @return array
	 */
	function selectKit(array $attr, array $option, $selected_value=null, $disabled_value=null) {
		static $pass;$html = null;$js = null;
		
		if (!$pass) {
			$pass = true;
			$this->set_css(URL_STATIC_FILE.M_PACKAGE.'/'.SITE_LANG.'/js/chosen_v1.4.2/chosen.min.css', 'href');
			$this->set_css(URL_STATIC_FILE.M_PACKAGE.'/'.SITE_LANG.'/js/chosen_v1.4.2/websemantics-Image-Select-86ccf22/ImageSelect.css', 'href');
			$this->set_js(URL_STATIC_FILE.M_PACKAGE.'/'.SITE_LANG.'/js/chosen_v1.4.2/chosen.jquery.min.js', 'src');
			$this->set_js(URL_STATIC_FILE.M_PACKAGE.'/'.SITE_LANG.'/js/chosen_v1.4.2/websemantics-Image-Select-86ccf22/ImageSelect.jquery.js', 'src');
		}
		
		$uniqid = isset($attr['id'])? $attr['id'] : $attr['id'] = uniqid();//id 防呆
		
		$html .= '<select '.array2htmlattr($attr).'>';
		
		if (!isset($attr['multiple'])) $html .= '<option value="">'._('Please select').'</option>';
		
		foreach ($option as $v0) {
			$selected = ($v0['value'] === $selected_value || (is_array($selected_value) && in_array($v0['value'], $selected_value)))? 'selected="selected"' : null;
			$disabled = ((is_array($disabled_value) && in_array($v0['value'], $disabled_value)) || $v0['value'] === $disabled_value)? 'disabled' : null;
			$html .= '<option value="'.$v0['value'].'" '.array2htmlattr(isset($v0['attribute'])? $v0['attribute'] : array()).' '.$selected.' '.$disabled.'>'.$v0['text'].'</option>';
		}
		$html .= '</select>';
		
		$js .= '
		$(function(){
			$("select[id=\"'.$uniqid.'\"]").chosen();
		});
		';
		
		return array($html, $js);
	}
	
	function set_css($css, $type='direct') {
		switch ($type) {
			case 'href':
				if (!in_array($css, self::$css_src)) self::$css_src[] = $css;
				break;
					
			default:
				self::$css .= $css;
				break;
		}
	}
	
	function set_js($js, $type='direct') {
		switch ($type) {
			case 'src':
				if (!in_array($js, self::$js_src)) self::$js_src[] = $js;
				break;
					
			default:
				self::$js .= $js;
				break;
		}
	}
	
	function set_jquery() {
		self::$js_src['jquery'] = URL_STATIC_FILE.M_PACKAGE.'/'.SITE_LANG.'/js/jquery-2.1.1.min.js';
	}
	
	function set_jquery_cookie() {
		static $pass;
		if (!$pass) {
			$this->set_jquery();
			$this->set_js(URL_ROOT.'js/jquery-cookie-master/src/jquery.cookie.js', 'src');
		}
	}
	
	function set_jquery_ui() {
		self::$js_src['jquery_ui'] = URL_ROOT.'js/jquery-ui/jquery-ui-1.10.4.custom.min.js';
		self::$css_src['jquery_ui'] = URL_ROOT.'js/jquery-ui/custom-theme/jquery-ui-1.10.4.custom.min.css';
	}
	
	function set_jquery_validation() {
		static $pass;
		if (!$pass) {
			$pass = true;
			
			$this->set_css(URL_STATIC_FILE.M_PACKAGE.'/'.SITE_LANG.'/js/jquery-validation-1.14.0/dist/validate.css', 'href');
			$this->set_js(URL_STATIC_FILE.M_PACKAGE.'/'.SITE_LANG.'/js/jquery-validation-1.14.0/dist/jquery.validate.min.js', 'src');
			$this->set_js(URL_STATIC_FILE.M_PACKAGE.'/'.SITE_LANG.'/js/jquery-validation-1.14.0/dist/additional-methods.min.js', 'src');
			$this->set_js(URL_STATIC_FILE.M_PACKAGE.'/'.SITE_LANG.'/js/jquery-validation-1.14.0/dist/localization/messages_zh_TW.min.js', 'src');
			
			self::$js .= '
			$.validator.setDefaults({
				errorElement: "div",
				errorPlacement: function(error, element) {
            		error.insertAfter(element).addClass("validate-message").offset({left: element.offset().left + element.outerWidth() + 2, top: (element.offset().top + element.outerHeight() / 2) - (error.outerHeight() / 2)});
				},
				wrapper: "div",
			});
			$.validator.addMethod("is_letter_and_number", function(value, element) {
				return this.optional(element) || /^[a-zA-Z0-9]+$/.test(value);
			}, "'._('Please enter only letters and numbers.').'");
			$.validator.addMethod("is_windows_filename", function(value, element) {
				return this.optional(element) || !/[\/\?\*:"<>|\\\]+/.test(value);
			}, "'._('Name can not contain any of the following characters: /?*:\"<>|\\\\').'");
			$.validator.addMethod("is_url_but_not_allow_https", function(value, element) {
				return this.optional(element) || !/^https{1}/i.test(value);
			}, "'._('Not allow https').'");
			$.validator.addMethod("not_allow_underline", function(value, element) {
				return this.optional(element) || !/_+/.test(value);
			}, "'._('Not allow underline').'");
			$.validator.addMethod("not_allow_dash", function(value, element) {
				return this.optional(element) || !/-+/.test(value);
			}, "'._('Not allow dash').'");
			';
		}
	}
	
	function submit($attr) {
		static $pass;$html = null;$js = null;
		if (!$pass) {
			$pass = true;
		}
		$html .= '<input type="submit" '.$attr.'>';
		
		return array($html, $js);
	}
	
	function submit_act($attr, $submit_act) {
		static $pass;$html = null;$js = null;
		if (!$pass) {
			$pass = true;
			$html .= '<input type="hidden" id="submit_act" name="submit_act" value="">';
		}
		$html .= '<input type="button" '.$attr.' onclick="document.getElementById(\'submit_act\').value = \''.$submit_act.'\';this.disabled = true;this.form.action = window.location.href;this.form.method = \'post\';this.form.submit();">';
	
		return [$html, $js];
	}
	
	function table($attr=null, $column=array(), $extra=null) {
		static $pass;$html = null;$js = null;
		if (!$pass) {
			$pass = true;
			self::$css .= '
			.table{
				width: 100%;
			}
			.table-tr-head {
				border-bottom: 1px dotted #cccccc;
			}
			.table-tr-body {
				border-bottom: 1px dotted #cccccc;
			}
			.table-tr-foot {
			}
			.table-td {
				padding: 10px 0;
			}
			.table-td-key {
				width:12%;
			}
			.table-td-value {
				width:88%;
			}
			.table-tdkey-remark {
				color: #9f9f9f;
			}
			';
		}
		$html = '<table '.$attr.'>';
		if (!empty($column)) {
			$c_column = count($column);
			foreach ($column as $k0 => $v0) {
				$trattr = isset($v0['trattr'])? $v0['trattr'] : null;
				$tdkeyattr = isset($v0['tdkeyattr'])? $v0['tdkeyattr'] : null;
				$key_remark = isset($v0['key_remark'])? '<br><span class="table-tdkey-remark">'.$v0['key_remark'].'</span>' : null;
				$tdvalueattr = isset($v0['tdvalueattr'])? $v0['tdvalueattr'] : null;
				if ($k0 == $c_column - 1) {
					$tr_class = 'class="table-tr-foot"';
				} elseif ($k0 == 0) {
					$tr_class = 'class="table-tr-head"';
				} else {
					$tr_class = 'class="table-tr-body"';
				}
				$html .= '<tr '.$tr_class.' '.$trattr.'>';
				$html .= '<td class="table-td table-td-key" '.$tdkeyattr.'>'.$v0['key'].$key_remark.'</td>';
				$html .= '<td class="table-td table-td-value" '.$tdvalueattr.'>'.$v0['value'].'</td>';
				$html .= '</tr>';
			}
		}
		$html .= '</table>'.$extra;
	
		return [$html, $js];
	}
	
	function tabKit() {
		static $pass;
		if (!$pass) {
			$pass = true;
			$this->set_jquery();
			$this->set_css(URL_STATIC_FILE.M_PACKAGE.'/'.SITE_LANG.'/js/telerik.kendoui.professional.2016.1.112.trial/styles/kendo.common.min.css', 'href');
			$this->set_css(URL_STATIC_FILE.M_PACKAGE.'/'.SITE_LANG.'/js/telerik.kendoui.professional.2016.1.112.trial/styles/kendo.silver.min.css', 'href');
			$this->set_js(URL_STATIC_FILE.M_PACKAGE.'/'.SITE_LANG.'/js/telerik.kendoui.professional.2016.1.112.trial/js/kendo.all.min.js', 'src');
		}
	}
	
	function tabs($param) {
		static $pass;$js = null;
		if (!$pass) {
			$pass = true;
			self::$css .= '
			#tabs {
				min-width: 1140px;
			}
			';
			self::$js .= '
			$(function() {
				$("#tabs").tabs({
					activate: function(event, ui) {
						//在切換 tabs 時 lazyload 會無法激活(縮放視窗可以), 因此自行在切換 tabs 時置換 img src, 參照 http://cyclopslabs.com/usin-lazyload-in-jquery-tabs/  
						$("img", ui.newPanel).each(function() {
							var $this = $(this);
							if ($this.attr("data-original").length) $this.attr({"src": $this.attr("data-original"), "data-original": ""});
						});
					}
				});
			});
			';
		}
		ksort($param);
		$html = '<div id="tabs"><ul>';
		foreach ($param as $v1) {
			$html .= '<li><a href="'.$v1['href'].'">'.$v1['name'].'</a></li>';
		}
		$html .= '</ul>';
		foreach ($param as $k1 => $v1) {
			if ('#' == substr($v1['href'], 0, 1)) {
				$html .= '<div id="'.substr($v1['href'], 1).'">'.$v1['value'].'</div>';
			}
		}
		$html .= '</div>';
		
		return [$html, $js];
	}
	
	function text($attr) {
		return array('<input type="text" '.$attr.'>', null);
	}
	
	function textarea($attr, $text=null) {
		return array('<textarea '.$attr.'>'.$text.'</textarea>', null);
	}
	
	function time($attr) {
		static $pass;$html = null;$js = null;$uniqid = uniqid();
		if (!$pass) {
			$pass = true;
			$this->set_js(URL_ROOT.'js/jquery-timepicker/jquery-ui-timepicker-addon.js', 'src');
			$this->set_js(URL_ROOT.'js/jquery-timepicker/localization/jquery-ui-timepicker-zh-TW.js', 'src');
			$this->set_css(URL_ROOT.'js/jquery-timepicker/jquery-ui-timepicker-addon.css', 'href');
			
			self::$js .= '
			$(window).load(function(){
				$("input[data-kit=\'time\']").timepicker();
			});
			';
		}
		$html.= '<input id="'.$uniqid.'" type="text" readonly="readonly" size="12" '.$attr.' data-kit="time">';
	
		return array($html, $js);
	}
	
	function upload($attr=null, array $allow_extension=[]) {
		static $pass;
		$html = null;$js = null;
		
		//id 防呆
		$uniqid = explode_attr($attr, 'id', '"');
		if (empty($uniqid)) {
			$uniqid = uniqid();
			$attr = 'id="'.$uniqid.'" '.$attr;
		}
		
		if (!$pass) {
			$pass = true;
			self::$css .= '
				.fileupload-panel {
				    float: left;
				    height: inherit;
				    width: 580px;
				}
				.fileupload-panel input[type="text"] {
				    float: left;
				    margin: 0 5px 0 0;
				    position: relative;
				    top: 40%;
				    width: 300px;
				}
				.fileupload-panel-mask {
					background-color: rgba(0, 0, 0, 0);
				    display: none;
				    height: inherit;
				    left: 0;
				    position: absolute;
				    top: 0;
				    width: inherit;
				    z-index: 10;
				}
				.fileupload-panel-text {
					display: inline-block;
					text-align: center;
					vertical-align: middle;
					width: inherit;
				}
				.fileupload-panel-button {
					background-color: #6eca6e;
					border-radius: 4px;
					color: #ffffff;
					float: left;
					font-size: 15px;
					height: 30px;
					margin: 0 5px 0 0;
					overflow: hidden;
					position: relative;
					top: 35%;
					transition: all 0.3s ease 0s;
					width: 90px;
				}
				.fileupload-panel-button:before {
					content: "";
					display: inline-block;
					height: inherit;
					vertical-align: middle;
				}
				.fileupload-panel-button:hover {
					background-color: #549C54;
				}
				.fileupload-panel-button-drag {
					background-color: #549C54;
					height: 100px;
				    top: 0;
				    width: 100px;
				}
				.fileupload-panel-button-file {
				    cursor: pointer;
				    font-size: 30px;
				    left: 0;
				    margin: 0;
				    opacity: 0;
				    padding: 0;
				    position: absolute;
				    top: 0;
				}
				.fileupload-panel-progress {
					display: none;
					float: left;
				    padding-top: 6px;
				    position: relative;
				    top: 35%;
				}';
			
			/**
			 * 參照說明 4.5.
			 * myXhr.upload -> check if upload property exists
			 * myXhr.upload.addEventListener -> for handling the progress of the upload
			 * $progress.attr("value", $progress.attr("max")).siblings("span").text("100%"); -> 有時會發生 progress 沒跑到 100% 的情況, 在這裡補滿
			 */
			self::$js .= '
				function fileupload_process(obj, files) {
					if (!files.length) return;
					
					var id = $(obj).data("id"),
						$progress = $("progress[id=\'progress-"+ id +"\']"),
						$progress_text = $("span[id=\'progress-text-"+ id +"\']"),
						$fileupload_panel_progress = $("div[id=\'fileupload-panel-progress-"+ id +"\']"),
						$input = $("input[id=\'"+ id +"\']");
						
						//deploy by image
						$img = $("img[id=\'"+ id +"-img\']");
						if ($img.length) {
							var $a = $("a[id=\'"+ id +"-a\']");
						}
						
					var formData = new FormData(),
						number_of_files_upload_limit = 1;
					for (var i = 0; i < files.length; i++) {
						var name = files[i].name,
							size = files[i].size,
							type = files[i].type;
						formData.append("file", files[i]);
					
						if ($img.length) {
							formData.append("filetype", "image");
							formData.append("width", $img.data("width"));
							formData.append("height", $img.data("height"));
						}	
					
						if (i >= number_of_files_upload_limit) {
							alert("'._('The number of files to upload limit is reached').'");
							break;
						}
					}
					$.ajax({
						url: "'.\Core::controller()->url('upload', 'upload', ['class'=>M_CLASS]).'",
						type: "POST",
						xhr: function(){
							var myXhr = $.ajaxSettings.xhr();
							if (myXhr.upload) {
								myXhr.upload.addEventListener("progress", function(e){
				        			if (e.lengthComputable) {
				        				$progress.prop({value:e.loaded, max:e.total});
										$progress_text.text(Math.ceil(e.loaded / e.total * 100) + "%");
				        		    }
				            	}, false);
							}
							return myXhr;
						},
						beforeSend: function(){
							$fileupload_panel_progress.fadeIn();
							$(this).attr("disabled", "disabled");
						},
						success: function(r){
							r = $.parseJSON(r);
							if (r.result) {
								$input.prop("value", r.data.file_folder);
								
								if ($img.length) {
									$a.prop({title: r.data.file_name, href: r.data.file_url});
									$img.prop({src: r.data.file_thumbnail_url});
								}
								
								setTimeout(function(){
									$progress.attr("value", $progress.attr("max")).siblings("span").text("100%");
									fileupload_finish(id);
								}, 1500);
							} else {
								formerror_v2(r);
								setTimeout(function(){fileupload_finish(id);}, 1500);
							}
						},
						error: function(){
							alert("'._('File transfer error in client, please try again').'");
							setTimeout(function(){fileupload_finish(id);}, 1500);
						},
						data: formData,
						cache: false,
						contentType: false,
						processData: false
					});
				}
				function fileupload_finish(id) {
					$("div[id=\'fileupload-panel-progress-"+ id +"\']").fadeOut(function(){
						$("progress[id=\'progress-"+ id +"\']").attr("value", 0);
						$("span[id=\'progress-text-"+ id +"\']").text("0%");
						$("input[id=\'file-"+ id +"\']").removeAttr("disabled");
					});
				}
				$(document).on("change", ".fileupload-panel-button-file", function(e){fileupload_process(this, this.files);});
				$(document).on({
					dragenter: function(e) {e.stopPropagation();e.preventDefault();$(this).addClass("fileupload-panel-button-drag").find(".fileupload-panel-mask").show();},
					dragover: function(e) {e.stopPropagation();e.preventDefault();},
					dragleave: function(e) {e.stopPropagation();e.preventDefault();},
					drop: function(e) {e.stopPropagation();e.preventDefault();}
				}, ".fileupload-panel-button");
				$(document).on({
					dragenter: function(e) {e.stopPropagation();e.preventDefault();},
					dragover: function(e) {e.stopPropagation();e.preventDefault();},
					dragleave: function(e) {e.stopPropagation();e.preventDefault();$(this).hide().parent().removeClass("fileupload-panel-button-drag");},
					drop: function(e) {e.stopPropagation();e.preventDefault();$(this).hide().parent().removeClass("fileupload-panel-button-drag");fileupload_process($(this).siblings("input:file"), e.originalEvent.dataTransfer.files);}
				}, ".fileupload-panel-mask");
				$(document).on({
					dragenter: function(e) {e.stopPropagation();e.preventDefault();},
					dragover: function(e) {e.stopPropagation();e.preventDefault();},
					dragleave: function(e) {e.stopPropagation();e.preventDefault();},
					drop: function(e) {e.stopPropagation();e.preventDefault();}
				});';
		}
		
		$html .= '<div class="fileupload-panel">';
		$html .= '<div class="fileupload-panel-button"><div class="fileupload-panel-mask"></div><p class="fileupload-panel-text">'._('Upload').'</p><input type="file" id="file-'.$uniqid.'" class="fileupload-panel-button-file" name="file" data-id="'.$uniqid.'" accept="'.implode(',', $allow_extension).'"></div>';
		$html .= '<input type="text" '.$attr.' readonly>';
		$html .= '<div id="fileupload-panel-progress-'.$uniqid.'" class="fileupload-panel-progress"><progress id="progress-'.$uniqid.'" max="1" value="0"></progress>&emsp;<span id="progress-text-'.$uniqid.'">0%</span></div>';
		$html .= '</div>';
		
		return [$html, $js];
	}
	
	function upload_show_image($attr) {
		static $pass;
		$html = null;$js = null;
		
		//id 防呆
		$uniqid = explode_attr($attr, 'id', '"');
		if (empty($uniqid)) {
			$uniqid = uniqid();
			$attr = 'id="'.$uniqid.'" '.$attr;
		}
		
		$href = $src = explode_attr($attr, 'value', '"');
		if (!$pass) {
			$pass = true;
			self::$css .= '
			.'.__FUNCTION__.'-div{
				height: 100px;
				width: 850px;
			}
			.'.__FUNCTION__.'-div div{
				margin: 0 5px 0 0;
			}
			.'.__FUNCTION__.'-a {
			    float: left;
			    position: relative;
			}
			.'.__FUNCTION__.'-input {
			    float: left;
			    position: relative;
			    top: 40%;
			}
			.'.__FUNCTION__.'-upload {
			    float: left;
			    height: inherit;
			    position: relative;
			}
			';
			
			list($html_imagebox, $js_imagebox) = $this->imagebox('.'.__FUNCTION__.'-a a');
			$html .= $html_imagebox;
			$js .= $js_imagebox;
		}
		
		$url_upload_src = null;
		if (!empty($href)) {
			$tmp1 = pathinfo($href);
			$dirname = $tmp1['dirname'];
			$extension = strtolower($tmp1['extension']);
			$filename = $tmp1['filename'];
			switch ($extension) {
				//imagick: pdf to jpeg
				case 'pdf':
					$href = $src = $dirname.'/'.$filename.'.jpeg';
					break;
			}
			$url_upload_src = URL_UPLOAD.getimageresize($src);
		}
		
		$html .= '<div class="'.__FUNCTION__.'-div">';
		$html .= '<div class="'.__FUNCTION__.'-a">';
		$html .= '<a id="'.$uniqid.'-a" title="" href="'.URL_UPLOAD.$href.'"><img id="'.$uniqid.'-img" border="0" data-original="'.$url_upload_src.'"></a>';
		$html .= '</div>';
		
		$html .= '<div class="'.__FUNCTION__.'-input"><input type="text" '.$attr.' size="64" readonly></div>';
		
		$html .= '<div class="'.__FUNCTION__.'-upload">';
		
		list($html_upload, $js_upload) = $this->upload($attr);
		$html .= $html_upload;
		$js .= $js_upload;
		
		$html .= '</div>';
		$html .= '</div>';
		
		return array($html, $js);
	}

	function urltable($attr, $value=array()) {
		static $pass;$html = null;$js = null;
		if (!$pass) {
			$pass = true;
		}
	
		$id = explode_attr($attr, 'id', '"');
		$name = explode_attr($attr, 'name', '"');
		
		$set = array(
				'id',
				'name',
				'target',
				'href'
		);
		
		$html .= '<table>';
		
		foreach ($set as $v1) {
			//在最後串上[], 引用此函示的各支提交處理的 js, 以切割字串取得此處的 $v1
			$s_id = $id.'['.$v1.']';
			$s_name = $name.'['.$v1.']';
			
			$s_attr = str_replace(
					array('id="'.$id.'"', 'name="'.$name.'"'),
					array('id="'.$s_id.'"', 'name="'.$s_name.'"'),
					$attr
			);
			
			if (isset($value[$v1])) {
				$s_attr .= ' value="'.$value[$v1].'"';
			}
			
			switch ($v1) {
				case 'id':
					$html .= '<input type="hidden" '.$s_attr.'>';
					break;
					
				case 'name':
					$html .= '<tr>';
					$html .= '<td width="20%">name : </td>';
					$html .= '<td><input type="text" size="32" maxlength="64" '.$s_attr.'></td>';
					$html .= '</tr>';
					break;
					
				case 'target':
					if (empty($value[$v1])) $value[$v1] = '_self';
					list($html_radiotable, $js_radiotable) = $this->radiotable('150px', '30px', 5, array(array('name'=>$s_name, 'value'=>'_self', 'text'=>'_self'), array('name'=>$s_name, 'value'=>'_blank', 'text'=>'_blank')), $value[$v1]);
					$html .= '<tr>';
					$html .= '<td width="20%">target : </td>';
					$html .= '<td>'.$html_radiotable.'</td>';
					$html .= '</tr>';
					$js .= $js_radiotable;
					break;
						
				case 'href':
					$html .= '<tr>';
					$html .= '<td width="20%">href : </td>';
					$html .= '<td><input type="url" size="64" maxlength="128" '.$s_attr.'></td>';
					$html .= '</tr>';
					break;
			}
		}
	
		$html .= '</table>';
		
		return array($html, $js);
	}
}
