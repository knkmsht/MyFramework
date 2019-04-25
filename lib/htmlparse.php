<?php

namespace lib;

class htmlparse
{
    private
        $attribute,
        $dom,
        $tagname;

    function __construct()
    {
        $this->dom = new \DOMDocument();
    }

    public function set($html)
    {
        if (is_file($html)) {
            $this->dom->loadHTMLFile($html);
        } else {
            $this->dom->loadHTML($html);
        }

        return $this;
    }

    public function setTagName($name)
    {
        $this->tagname = $name;

        return $this;
    }

    public function setAttribute($attribute)
    {
        $this->attribute = $attribute;

        return $this;
    }

    public function get()
    {
        $array = [];

        if (isset($this->tagname)) {
            $object_element = $this->dom->getElementsByTagName($this->tagname);

            foreach ($object_element as $element) {
                if (isset($this->attribute)) {
                    $attribute = $element->getAttribute($this->attribute);

                    if ($attribute !== '') {
                        $array[] = $attribute;
                    }
                } else {
                    $cloned = $element->cloneNode(true);

                    $dom = new \DOMDocument();

                    $dom->appendChild($dom->importNode($cloned, true));

                    $array[] = htmlspecialchars($dom->saveHTML());
                }
            }
        }

        return $array;
    }
}