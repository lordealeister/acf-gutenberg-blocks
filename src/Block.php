<?php

namespace AcfGutenbergBlocks;

use WordPlate\Acf\Location;

/**
 * Block Class to register block fields
 *
 * @package ACFGutenbergBlocks
 * @author Lorde Aleister
 * @access public
 */
class Block {

    /**
     * title Block title
     *
     * @var string
     */
    protected $title = '';

    /**
     * name Block name
     *
     * @var string
     */
    private $name = '';

    public function __construct() {
        // Get block name from file
        $reflection = new \ReflectionClass($this);
        $this->name = strtolower(preg_replace('/([a-zA-Z])(?=[A-Z])/', '$1-', $reflection->getShortName()));

        // Insert preview
        add_filter("sage/blocks/{$this->name}/register-data", array($this, 'previewData'));
        // Modify block data
        add_filter("sage/blocks/{$this->name}/data", array($this, 'blockData'));
        // Filter content
        add_filter('render_block', array($this, 'render'), 1, 2);

        // Register field group
        $this->makeSettings();
    }

    /**
     * makeSettings Register a new field group in ACF
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
     * preview Register block preview
     *
     * @return array
     */
    protected function preview(): array {
        return [];
    }

    /**
     * previewData Insert preview to register block
     *
     * @param  array $data Current block data
     * @return array Modified block data
     */
    public function previewData(array $data): array {
        $previewData = $this->preview();

        if(empty($previewData))
            return $data;

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
     * allowedInnerBlocks Registers the blocks that can be inserted in the block content
     *
     * @return array Allowed blocks list
     */
    protected function allowedInnerBlocks(): array {
        return [];
    }

    /**
     * blockData Modify block var passed to template
     *
     * @param  array $block Current block data
     * @return array Modified block data
     */
    public function blockData(array $block): array {
        // TODO: get from render and insert into block data
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
     * content Filter block content
     *
     * @param  string $content Current content
     * @return string Modified content
     */
    protected function content(string $content): string {
        return $content;
    }

    /**
     * render Filter block render
     *
     * @param  string $block_content Block content output
     * @param  array $block Block data
     * @return string Modified block content
     */
    public function render(string $block_content, array $block): string {
        if($block['blockName'] != "acf/{$this->name}")
            return $block_content;

        return $this->content($block_content);
    }

    /**
     * fields Set Wordplate block fields
     *
     * @return array Fields list
     */
    protected function fields(): array {
        return [];
    }

}
