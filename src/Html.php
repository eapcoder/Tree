<?php

declare(strict_types=1);

namespace Tree;

class Html
{
    public ?bool $withRoot = false;
    public static ?string $html = '';

    public function __construct(private $tree = null)
    {
        
    }

    public static function generate($tree = null) {
        
        self::$html .= '<ul>';
        foreach($tree as $item) {

            if (count($item['children'])) {
                self::$html .= '<li>' . $item['name'] . '</li>';
                self::generate($item['children']);
                
            } else {
                self::$html .= '<li>' . $item['name'] . '</li>';
            }
        }
        self::$html .= '</ul>';
        return self::$html;
    }
}