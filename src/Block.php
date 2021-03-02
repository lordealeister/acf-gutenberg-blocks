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

        add_filter("sage/blocks/{$this->name}/data", function($block) {
            return array_merge(
                $block,
                array(
                    'data' => field($this->name),
                )
            );
        });
    }

    /**
     * make
     *
     * @return array
     */
    abstract protected function make(): array;

}
