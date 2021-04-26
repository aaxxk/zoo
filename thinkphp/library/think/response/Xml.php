<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

namespace think\response;

use think\Collection;
use think\Model;
use think\Response;

class Xml extends Response
{
    // Output parameters
    protected $options = [
        // Root node name
        'root_node' => 'think',
        // Root node attributes
        'root_attr' => '',
        //The name of the child node of the numerical index
        'item_node' => 'item',
        // The attribute name of the key conversion of the digital index child node
        'item_key'  => 'id',
        // Data encoding
        'encoding'  => 'utf-8',
    ];

    protected $contentType = 'text/xml';

    /**
     * Data processing
     * @access protected
     * @param mixed $data Data to be processed
     * @return mixed
     */
    protected function output($data)
    {
        // XML data conversion
        return $this->xmlEncode($data, $this->options['root_node'], $this->options['item_node'], $this->options['root_attr'], $this->options['item_key'], $this->options['encoding']);
    }

    /**
     * XML encoding
     * @param mixed $data data
     * @param string $root Root node name
     * @param string $item The name of the child node of the numerical index
     * @param string $attr Root node attributes
     * @param string $id   The attribute name of the key conversion of the digital index child node
     * @param string $encoding Data encoding
     * @return string
     */
    protected function xmlEncode($data, $root, $item, $attr, $id, $encoding)
    {
        if (is_array($attr)) {
            $array = [];
            foreach ($attr as $key => $value) {
                $array[] = "{$key}=\"{$value}\"";
            }
            $attr = implode(' ', $array);
        }
        $attr = trim($attr);
        $attr = empty($attr) ? '' : " {$attr}";
        $xml  = "<?xml version=\"1.0\" encoding=\"{$encoding}\"?>";
        $xml .= "<{$root}{$attr}>";
        $xml .= $this->dataToXml($data, $item, $id);
        $xml .= "</{$root}>";
        return $xml;
    }

    /**
     * Data XML encoding
     * @param mixed  $data data
     * @param string $item Node name in digital index
     * @param string $id   The attribute name that the numeric index key is converted to
     * @return string
     */
    protected function dataToXml($data, $item, $id)
    {
        $xml = $attr = '';

        if ($data instanceof Collection || $data instanceof Model) {
            $data = $data->toArray();
        }

        foreach ($data as $key => $val) {
            if (is_numeric($key)) {
                $id && $attr = " {$id}=\"{$key}\"";
                $key         = $item;
            }
            $xml .= "<{$key}{$attr}>";
            $xml .= (is_array($val) || is_object($val)) ? $this->dataToXml($val, $item, $id) : $val;
            $xml .= "</{$key}>";
        }
        return $xml;
    }
}
