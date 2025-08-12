<?php

declare(strict_types=1);

namespace Tree\Helpers;

class Html
{
    public ?bool $withRoot = false;
    public static ?string $html = '';

    public function __construct(private $tree = null)
    {
        
    }

    public static function generate($tree = null, $dev = false) {
        
        self::$html .= '<ul>';
        foreach($tree as $item) {

            if (count($item['children'])) {
                self::$html .= '<li>' . $item['name'] . (($dev) ? ' <span class="var"> ' . $item['lft'] . '</span> - <span class="var">'. $item['rgt'] . "</span>" : "") .  '</li>';
                self::generate($item['children'], $dev);
                
            } else {
                self::$html .= '<li>' . $item['name'] . (($dev) ? ' <span class="var"> ' . $item['lft'] . '</span> - <span class="var">' . $item['rgt'] . "</span>" : "") . '</li>';
            }
        }
        self::$html .= '</ul>';
        return self::$html;
    }
}