<?php

namespace AcfGutenbergBlocks;

use WordPlate\Acf\Fields\Group;
use WordPlate\Acf\Location;

/**
 * Block Abstract class to register block fields
 */
abstract class Block {

    protected $title = '';
    protected $instructions = '';

    private $name = '';

    public function __construct() {
        $reflection = new \ReflectionClass($this);
        $this->name = strtolower(preg_replace('/([a-zA-Z])(?=[A-Z])/', '$1-', $reflection->getShortName()));

        add_filter("sage/blocks/{$this->name}/register-data", function($data) {
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
        });

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
            'title'    => ' ',
            'fields'   => [
                Group::make($this->title, $this->name)
                    ->instructions($this->instructions)
                    ->layout('block')
                    ->fields($this->make())
            ],
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
     * make
     *
     * @return array
     */
    abstract protected function make(): array;

}
