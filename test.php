<?php
$items = [
    ['id' => 1, 'name' => 'Category A', 'parent_id' => null],
    ['id' => 2, 'name' => 'Category B', 'parent_id' => 1],
    ['id' => 3, 'name' => 'Category C', 'parent_id' => null],
    ['id' => 4, 'name' => 'Category D', 'parent_id' => 2],
    ['id' => 5, 'name' => 'Category E', 'parent_id' => 3],
];


function buildTree(array $elements, $parentId = null)
{
    $branch = [];
    foreach ($elements as $element) {
        if ($element['parent_id'] === $parentId) {
            $children = buildTree($elements, $element['id']);
            if ($children) {
                $element['children'] = $children;
            }
            $branch[] = $element;
        }
    }
    return $branch;
}

$tree = buildTree($items);
print '<pre>';
print_r($tree);