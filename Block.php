<?php

namespace AcfGutenbergBlocks;

use WordPlate\Acf\Fields\Group;
use WordPlate\Acf\Location;

/**
 * Block Abstract class to register block fields
 */
abstract class Block {

    protected $name = 'block';
    protected $title = '';
    protected $instructions = 'Configurações do bloco.';
    protected $fields = array();

    public function __construct() {
        $this->make();
        $this->makeSettings();
    }

    /**
     * makeSettings
     *
     * @return void
     */
    protected function makeSettings(): void {
        register_extended_field_group([
            'key'      => "group_{$this->name}",
            'title'    => ' ',
            'fields'   => [
                Group::make($this->title, "{$this->name}_settings")
                    ->instructions($this->instructions)
                    ->layout('block')
                    ->fields($this->fields)
            ],
            'location' => [
                Location::if('block', "acf/{$this->name}")
            ],
        ]);

        add_filter("sage/blocks/{$this->name}/data", function($block) {
            return array_merge(
                $block,
                array(
                    'data' => field("{$this->name}_settings"),
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
