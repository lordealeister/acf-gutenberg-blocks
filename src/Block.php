<?php

namespace AcfGutenbergBlocks;

use WordPlate\Acf\Location;

/**
 * Block Class to register block fields
 */
class Block {

    protected $title = '';

    private $name = '';

    public function __construct() {
        $reflection = new \ReflectionClass($this);
        $this->name = strtolower(preg_replace('/([a-zA-Z])(?=[A-Z])/', '$1-', $reflection->getShortName()));

        add_filter("sage/blocks/{$this->name}/register-data", array($this, 'previewData'));
        add_filter("sage/blocks/{$this->name}/data", array($this, 'blockData'));
        add_filter('render_block', array($this, 'render'), 1, 2);

        $this->makeSettings();
    }

    /**
     * makeSettings
     *
     * @return void
     */
    protected function makeSettings(): void {
        register_extended_field_group([
            'key'      => "block_{$this->name}",
            'title'    => $this->title,
            'fields'   => $this->make(),
            'location' => [
                Location::if('block', "acf/{$this->name}")
            ],
        ]);
    }

    /**
     * preview
     *
     * @return array
     */
    protected function preview(): array {
        return [];
    }

    /**
     * allowedInnerBlocks
     *
     * @return array
     */
    protected function allowedInnerBlocks(): array {
        return [];
    }

    /**
     * previewData
     *
     * @param  array $data
     * @return array
     */
    public function previewData(array $data): array {
        $previewData = $this->preview();

            if(empty($previewData))
                return $data;

            $previewData = array_combine(
                array_map(function($key) {
                    return "{$this->name}_{$key}";
                }, array_keys($previewData)),
                $previewData
            );

            $previewData = array_merge($previewData, array('is_preview' => true));

            $data['example'] = array(
                'attributes' => array(
                    'mode' => 'preview',
                    'data' => $previewData,
                ),
            );

        return $data;
    }

    /**
     * blockData
     *
     * @param  array $block
     * @return array
     */
    public function blockData(array $block): array {
        // TODO: Melhorar forma de obter innerBlocks
        $innerBlocks = array();

        $xml = new \DOMDocument();
        $xml->loadHTML($block['content']);
        $root = $xml->getElementsByTagName('body');

        foreach($root->item(0)->childNodes as $node):
            if(get_class($node) == 'DOMText')
                continue;

            array_push($innerBlocks, $node);
        endforeach;

        return array_merge(
            $block,
            array(
                'allowed_blocks' => wp_json_encode($this->allowedInnerBlocks()),
                'inner_blocks' => $innerBlocks,
            )
        );
    }

    /**
     * content
     *
     * @param  string $content
     * @return string
     */
    protected function content(string $content): string {
        return $content;
    }

    /**
     * render
     *
     * @param  string $block_content
     * @param  array $block
     * @return string
     */
    public function render(string $block_content, array $block): string {
        if($block['blockName'] != "acf/{$this->name}")
            return $block_content;

        return $this->content($block_content);
    }

    /**
     * make
     *
     * @return array
     */
    protected function make(): array {
        return [];
    }

}
