<?php
class Euleo_Dma
{
    protected static $config = array();
    
    public static function getConfig($type)
    {
        if (self::$config[$type]) {
            return self::$config[$type];
        }
        
        $db = Database::getInstance();
        
        $fields = $db->execute('
            SELECT
                tl_dma_eg_fields.type,
                tl_dma_eg_fields.label,
                tl_dma_eg_fields.title,
                tl_dma_eg_fields.pid
            FROM
                tl_dma_eg_fields
            LEFT JOIN
                tl_dma_eg ON tl_dma_eg.id = tl_dma_eg_fields.pid
            WHERE
                tl_dma_eg_fields.type IN ("text", "textarea")
                AND tl_dma_eg.content = 1
        ');
        
        while ($fields->next()) {
        	if ($fields->type == 'textarea') {
        		$fields->type = 'richtextarea';
        	}
        	
            self::$config['dma_eg_' . $fields->pid][htmlspecialchars($fields->title)] = array(
                'type' => $fields->type,
                'label' => htmlspecialchars($fields->label)
            );
        }
        
        return self::$config[$type];
    }
}
