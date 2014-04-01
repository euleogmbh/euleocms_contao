<?php
use Contao\Config;
use Contao\Controller;
use Contao\System;
use Contao\Database;
use Contao\PageModel;
/*
 This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

/**
 * Euleo-Backend for Contao Dca
 * @author Euleo GmbH
 */
class Euleo_Backend_Dca implements Euleo_Backend {
    /**
     * @var Controller
     */
    protected $module = null;
    
    /**
     * @var Euleo_Contao
     */
    protected $bridge = null;
    
    protected $tables = array('tl_page', 'tl_article', 'tl_content');
    
    protected $dca = null;
    
    protected $srcLang = 'de';
    
    protected $mode = 'new';

    /**
     * (non-PHPdoc)
     * @see Euleo_Backend::__construct()
     */
    public function __construct(Controller $module, Euleo_Contao $bridge)
    {
        $this->module = $module;
        
        $this->db = Database::getInstance();
        
        $mainPage = $this->db->execute('SELECT language FROM tl_page WHERE fallback = 1 AND type = "root"');
        
        $this->srcLang = $mainPage->language;
        
        $this->bridge = $bridge;
        
        $this->mode = $GLOBALS['TL_CONFIG']['euleo_mode'];
        
        $this->setPageLanguages();
    }
    
    /**
     * @see Euleo_Backend::getRows()
     */
    public function getRows($table, $id)
    {
        $this->dca = $this->loadDca();
        
        if ($table == 'tl_page') {
            $this->parsePage($id, $rows);
        } else if ($table == 'tl_article') {
            $this->parseArticle($id, $rows);
        } else if ($table == 'tl_content') {
            $this->parseContent($id, $rows);
        }
        
        return $rows;
    }
    
    /**
     * Parses Page by Id
     * @param int|Object $idOrObject
     * @param array $rows
     */
    protected function parsePage($idOrObject, &$rows)
    {
        if (is_object($idOrObject)) {
            $page = $idOrObject;
        } else {
            $stmt = $this->db->prepare('SELECT * FROM `tl_page` WHERE id = ?');
            $page = $stmt->execute($idOrObject)->first();
        }
        
        // FIXME: every page as srcLang
        if ($page->language != $this->srcLang) {
            throw new Exception('Only the pages on the fallback tree can be translated!');
        }
        
        if ($page->type == 'root') {
            $topPage = $page;
        } else {
            $topPage = $this->getRootLevelByPage($page->id);
        }
        
        if ($topPage->language) {
            $this->srcLang = $topPage->language;
        }
        
        $configFields = $this->dca['tl_page']['fields'];
        
        $stmt2 = $this->db->prepare('SELECT * FROM `tl_page` WHERE languageMain = ?');
        
        $fallback = $stmt2->execute($page->id);
        
        $row = array();
        $row['code'] = 'tl_page__' . $page->id;
        $row['title'] = $page->title;
        $row['srclang'] = $this->srcLang;
        $row['label'] = 'Page';
        $row['description'] = $row['label'] . ' "' . $row['title'] . '"';
        
        $fields = $this->getFields($page, $configFields, $fallback);
        
        if ($fields) {
            $row['fields'] = $fields;
        }
        
        $childRows = array();
        $this->getArticles($page->id, $childRows);
        
        if (count($childRows) > 0) {
            $row['rows'] = $childRows;
        }
        
        if ($row['rows'] || $fields) {
            $rows[] = $row;
        }
        
        $this->getChildPages($page->id, $rows);
    }
    
    protected function getArticles($pid, &$rows)
    {
        $stmt = $this->db->prepare('SELECT * FROM `tl_article` WHERE pid = ?');
        $article = $stmt->execute($pid);
        
        $configFields = $this->dca['tl_article']['fields'];
        
        while ($article->next()) {
            $stmt2 = $this->db->prepare('SELECT * FROM `tl_article` WHERE languageMain = ?');
            
            $fallback = $stmt2->execute($article->id);
            
            $row = array();
            $row['code'] = 'tl_article__' . $article->id;
            $row['title'] = $article->title;
            $row['srclang'] = $this->srcLang;
            $row['label'] = 'Article';
            $row['description'] = $row['label'] . ' "' . $row['title'] . '"';
            
            $fields = $this->getFields($article, $configFields, $fallback);
            
            if ($fields) {
                $row['fields'] = $fields;
            }
            
            $childRows = array();
            $this->getContents($article->id, $childRows, $fallback);
            
            if (count($childRows) > 0) {
                $row['rows'] = $childRows;
            }

            if ($row['rows'] || $fields) {
                $rows[] = $row;
            }
        }
    }
    
    protected function getContents($pid, &$rows)
    {
        $stmt = $this->db->prepare('SELECT * FROM `tl_content` WHERE pid = ? AND (ptable = "tl_article" OR ptable = "")');
        $content = $stmt->execute($pid);
        
        $configFields = $this->dca['tl_content']['fields'];
        
        while ($content->next()) {
            $stmt2 = $this->db->prepare('SELECT * FROM `tl_content` WHERE languageMain = ?');
            
            $fallback = $stmt2->execute($content->id);

            $row = array();
            $row['code'] = 'tl_content__' . $content->id;
            $row['title'] = $content->title;
            $row['srclang'] = $this->srcLang;
            $row['label'] = 'Content';
            $row['description'] = $row['label'] . ' "' . $row['title'] . '"';
            
            $fields = $this->getFields($content, $configFields, $fallback);
            if ($fields) {
                $row['fields'] = $fields;
            }
            
            if ($fields) {
                $rows[] = $row;
            }
        }
    }
    
    
    protected function getChildPages($pid, &$rows)
    {
        $stmt = $this->db->prepare('SELECT * FROM `tl_page` WHERE pid = ?');
        $page = $stmt->execute($pid);
        
        while ($page->next()) {
            $this->parsePage($page, $rows);
        }
    }
    
    protected function getFields($row, $configFields, $fallback)
    {
        $fields = array();
        
        // support for dma-eg-fields
        if (substr($row->type, 0, 6) == 'dma_eg') {
            $dma_fields = Euleo_Dma::getConfig($row->type);
        
            $data = unserialize($row->dma_eg_data);
            
            $fbData = unserialize($fallback->dma_eg_data);
            
            foreach ((array)$dma_fields as $fieldname => $config) {
                if ($fbData[$fieldname] && $this->mode == 'new') {
                    continue;
                }
                
                if ($data[$fieldname]) {
                    $field = array();
                    $field['label'] = $config['label'];
                    $field['type'] = $config['type'];
                    $field['value'] = $data[$fieldname];
                    
                    $fields['dma_eg_' . $fieldname] = $field;
                }
            }
        } else {
            foreach ($configFields as $index => $configField) {
                if ($configField['multilang']) {
                    if ($row->$index) {
                        if ($this->mode == 'new' && $fallback) {
                            if ($fallback->$index) {
                                continue;
                            }
                        }
                        
                        $field = array();
                        
                        $field['label'] = is_array($configField['label']) ? reset($configField['label']) : $configField['label'];
                        
                        $value = $row->$index;
                        
                        $field['type'] = $this->getFieldTypeByDca($configField['inputType'], $value);
                        $field['value'] = $value;
                        
                        if ($field['value']) {
                            $fields[$index] = $field;
                        }
                    }
                }
            }
        }
        
        return $fields;
    }
    
    protected function getFieldTypeByDca($inputType, &$value = false)
    {
        switch ($inputType) {
            case 'text':
                return 'text';
                break;
            case 'textarea':
                return 'richtextarea';
                break;
            default:
                $data = deserialize($value);
                
                if (is_array($data)) {
                    $value = $data['value'];
                }
                return 'text';
                break;
        }
        
        return 'text';
    }
    
    protected function getRootLevelByPage($id)
    {
        $query = "SELECT *, @pid:=pid FROM tl_page WHERE id=?"
               . str_repeat(" UNION SELECT *, @pid:=pid FROM tl_page WHERE id=@pid", 9);
        
        $parents = $objPages = \Database::getInstance()->prepare($query)->execute($id);
        
        while ($parents->next()) {
            if ($parents->type == 'root') {
                return $parents;
            }
        }
    }
    
    public function getLanguages()
    {
        $db = Database::getInstance();
    
        $return = array();
        $return['default'] = $this->srcLang;
        
        $objResult = $db->execute('SELECT language FROM tl_page WHERE pid = 0 AND fallback = 0 GROUP BY language');
    
        $return['languages'] = array();
        while($row = $objResult->next()) {
            $return['languages'][$row->language] = $row->language;
        }
    
        if (!$return['languages']) {
            throw new Exception('No page tree for target language set.');
        }
        
        return $return;
    }
    
    protected function getAssocById($tablename)
    {
        $result = $this->db->execute('SELECT * FROM `' . $tablename . '`');
        
        $return = array();
        
        while ($row = $result->row()) {
            $return[$row['id']] = $row;
        }
        
        return $return;
    }
    
    public function callback($rows)
    {
        $this->dca = $this->loadDca();
        
        return $this->handleRows($rows);
    }

    protected function setPageLanguages()
    {
        for ($i = 0; $i < 10; $i ++) {
            $this->db->execute('
                UPDATE
                    tl_page a
                JOIN
                    tl_page b
                SET
                    a.language = b.language WHERE a.pid = b.id AND b.language != ""
            ');
        }
    }
    
    protected function handleRows($rows)
    {
        $deliveredIds = array();
        
        foreach ((array)$rows as $row) {
            list($table, $id) = explode('__', $row['id']);
                
            if ($table == 'tl_page') {
                $this->handlePage($row, $id);
            }
            
            if ($row['ready'] == 1) {
                $deliveredIds[$row['translationid']] = $row['translationid'];
            }
        }
        
        if ($deliveredIds) {
            $this->bridge->confirmDelivery(array_keys($deliveredIds));
        }
        
        return true;
    }
    
    protected function handlePage($row, $id)
    {
        $page = $this->db->prepare('SELECT * FROM tl_page WHERE id = ?')->execute($id);
        
        $langRoot = $this->db->prepare('SELECT id FROM tl_page WHERE pid = 0 AND type = "root" AND language = ?')->execute($row['lang']);
        
        if (!$langRoot->id) {
            return false;
        }
        
        if ($page->type == 'root') {
            $existing = $langRoot->row();
        } else {
            $existing = $this->db->prepare('SELECT * FROM tl_page WHERE languageMain = ? AND language = ?')->execute($id, $row['lang'])->row();
        }        
        
        $update = array();
        
        // there is already a translation, update it
        if (!$existing['id']) {
            // create page in root of the target-tree with reference
            
            $result = $this->db->prepare('INSERT INTO tl_page SET pid = ?, languageMain = ?')->execute($langRoot->id, $id);
            
            $update = $page->row();
            $update['pid'] = $langRoot->id;
            $update['languageMain'] = $id;
            $update['language'] = $row['lang'];
            
            $existing['id'] = $result->insertId;
        }
        
        $update['language'] = $row['lang'];
        
        foreach ((array)$row['fields'] as $field) {
            $value = $this->handleFieldValue($field['name'], $field['value'], 'tl_page', $page);
            
            $update[$field['name']] = $value;
        }

        unset($update['id']);
        $updateId = $existing['id'];
        
        
        if ($update) {
            $result = $this->db->prepare('UPDATE tl_page %s WHERE id = ?')->set($update)->execute($updateId);
        }
        
        foreach ((array)$row['rows'] as $row) {
            list($table, $id) = explode('__', $row['id']);
            
            if ($table == 'tl_article') {
                $this->handleArticle($row, $id, $updateId);
            }
        }
    }
    
    protected function handleArticle($row, $id, $pageId)
    {
        $article = $this->db->prepare('SELECT * FROM tl_article WHERE id = ?')->execute($id);
        
        $existing = $this->db->prepare('
            SELECT
                tl_article.*
            FROM
                tl_article,
                tl_page
            WHERE
                tl_article.pid = tl_page.id
                AND tl_page.id = ?
                AND tl_article.languageMain = ?
                
        ')->execute($pageId, $id)->row();
        
        $update = array();
        
        // there is already a translation, update it
        if (!$existing['id']) {
            $result = $this->db->prepare('INSERT INTO tl_article SET pid = ?, languageMain = ?')->execute($pageId, $id);
            
            $update = $article->row();
            $update['pid'] = $pageId;
            $update['languageMain'] = $id;
                
            $existing['id'] = $result->insertId;
        }
        
        foreach ((array)$row['fields'] as $field) {
            $value = $this->handleFieldValue($field['name'], $field['value'], 'tl_article', $article);
            
            $update[$field['name']] = $value;
        }
        
        
        unset($update['id']);
        $updateId = $existing['id'];
        
        if ($update) {
            $result = $this->db->prepare('UPDATE tl_article %s WHERE id = ?')->set($update)->execute($updateId);
        }
        
        foreach ((array)$row['rows'] as $row) {
            list($table, $id) = explode('__', $row['id']);
                
            if ($table == 'tl_content') {
                $this->handleContent($row, $id, $updateId);
            }
        }
    }
    
    protected function handleContent($row, $id, $articleId)
    {
        $content = $this->db->prepare('SELECT * FROM tl_content WHERE id = ?')->execute($id);

        $existing = $this->db->prepare('
            SELECT
                tl_content.*
            FROM
                tl_content,
                tl_article
            WHERE
                tl_content.pid = tl_article.id
                AND tl_article.id = ?
                AND tl_content.languageMain = ?
                
        ')->execute($articleId, $id)->row();
        
        $update = array();
        
        // there is already a translation, update it
        if (!$existing['id']) {
            $result = $this->db->prepare('INSERT INTO tl_content SET pid = ?, languageMain = ?')->execute($articleId, $id);
                
            $update = $content->row();
            $update['pid'] = $articleId;
            $update['languageMain'] = $id;
        
            $existing['id'] = $result->insertId;
        }
        
        unset($update['dma_eg_data']);
        $egData = unserialize($existing['dma_eg_data']);
        
        if (!is_array($egData)) {
            $egData = array();
        }
        
        foreach ((array)$row['fields'] as $field) {
            if (substr($field['name'], 0, 6) == 'dma_eg') {
                $targetFieldName = substr($field['name'], 7);
                
                $egData[$targetFieldName] = $field['value'];

                $update['dma_eg_data'] = serialize($egData);
            } else {
                $value = $this->handleFieldValue($field['name'], $field['value'], 'tl_content', $content);
                
                $update[$field['name']] = $value;
            }
        }
        

        unset($update['id']);
        $updateId = $existing['id'];
        
        if ($update) {
            $result = $this->db->prepare('UPDATE tl_content %s WHERE id = ?')->set($update)->execute($updateId);
        }
    }
    
    protected function handleFieldValue($fieldName, $value, $table, $original)
    {
        $inputType = $this->dca[$table]['fields'][$fieldName]['inputType'];
            
        $data = deserialize($original->$fieldName);
        
        if (is_array($data)) {
            $data['value'] = $value;
    
            return serialize($data);
        }
    
        return $value;
    }
    
    protected function loadDca()
    {
        foreach ($this->tables as $table) {
            $this->module->loadDataContainer($table);
            $this->module->loadLanguageFile($table);
        }
        
        return $GLOBALS['TL_DCA'];
    }
}
