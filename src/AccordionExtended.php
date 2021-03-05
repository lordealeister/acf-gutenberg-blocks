<?php

namespace AcfGutenbergBlocks;

use WordPlate\Acf\Fields\Accordion;

/**
 * AccordionField Helper for creating an accordion field
 *
 * @package ACFGutenbergBlocks
 * @author Lorde Aleister
 * @access public
 */
class AccordionExtended {

    /**
     * make Create Wordplate field
     *
     * @param  mixed $title Field title
     * @param  mixed $name Field name
     * @param  mixed $icon Dashicon to be used next to the title
     * @return Field Field with settings
     */
    public static function make($title, $name, $icon = 'admin-generic') {
        return
            Accordion::make("<span class=\"dashicons dashicons-{$icon}\"></span> {$title}", "accordion_{$name}");
    }

}
