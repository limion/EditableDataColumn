<?php
/**
 * EditableDataColumn class file.
 *
 * @author Vladislav Holovko <vlad.holovko@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2012
 * @license http://www.yiiframework.com/license/
 */

Yii::import('zii.widgets.grid.CDataColumn');

class EditableDataColumn extends CDataColumn {
    
    public $inputType = 'text';
    
    public $inputTypeExpression;
    
    public $listData = array();
    
    public $inputHtmlOptions = array();
    
    public $varSuffix = '';
    
    public $idAttribute = 'id';
    
    public $errorStyle = '';
    
    public $errorClass = 'error';
    
    public $htmlBeforeInput;
    
    public $htmlAfterInput;
    
    protected $_isActiveRecord = false;
    
    public function init() {
		parent::init();
        if($this->name===null)
			throw new CException(Yii::t('editabledatacolumn','"name" must be specified for EditableDataColumn.'));
        if (!is_array($this->inputHtmlOptions)) {
            $this->inputHtmlOptions = array($this->inputHtmlOptions);
        }
	}
    
    /**
	 * Renders the data cell content.
	 * @param integer $row the row number (zero-based)
	 * @param mixed $data the data associated with the row
	 */
	protected function renderDataCellContent($row,$data) {
        // input type
        if($this->inputTypeExpression !== null)
            $this->inputType = $this->evaluateExpression($this->inputTypeExpression,array('data'=>$data,'row'=>$row));
        
        list($data,$attr) = $this->resolveData($data);
        // input name
        if (is_object($data)) {
            $postVar = get_class($data).$this->varSuffix;
            $this->_isActiveRecord = true;
        }
        else 
            $postVar = $this->varSuffix;
        $name = $postVar.'['.$data[$this->idAttribute].']['.$attr.']';
        
		// value
        if($this->value!==null)
			$value = $this->evaluateExpression($this->value,array('data'=>$data,'row'=>$row));
        elseif (isset($_POST[$postVar][$data[$this->idAttribute]])) {
            $data->$attr = $value = $_POST[$postVar][$data[$this->idAttribute]][$attr];
        }
        else 
            $value = CHtml::value($data,$attr);
        
        $inputHtmlOptions = $this->inputHtmlOptions;
        // check for validation errors
        $hasError = $this->_isActiveRecord && isset($_POST[$postVar][$data->{$this->idAttribute}][$attr]) ? $data->validate(array($attr)) === false : false;
        if ($hasError) {
            $inputHtmlOptions['title'] = $data->getError($attr);
            if ($this->errorStyle) 
                $inputHtmlOptions['style'] = isset($inputHtmlOptions['style']) ? 
                    $inputHtmlOptions['style'].'; '.$this->errorStyle :
                    $this->errorStyle;
            else
                $inputHtmlOptions['class'] = isset($inputHtmlOptions['class']) ? 
                    $inputHtmlOptions['class'].' '.$this->errorClass :
                    $this->errorClass;
        }
        $inputHtmlOptions = array_merge($inputHtmlOptions,array(
            'placeholder'=>$this->_isActiveRecord ? $data->getAttributeLabel($attr) : $attr
        ));
        if ($this->htmlBeforeInput && is_string($this->htmlBeforeInput))
            echo $this->evaluateExpression($this->htmlBeforeInput,array('data'=>$data,'row'=>$row));
        if (is_array($this->inputType)) {
            //extension
            $config = $this->inputType;
            $class = $config['class'];
            unset($config['class']);
            $config = array_merge($config,array(
                'name'=>$name,
                'value'=>$value,
            ));
            $this->grid->controller->widget($class,$config);
        }
        else {
            switch($this->inputType) {
                case 'number':
                    $inputHtmlOptions = array_merge($inputHtmlOptions,array(
                        'type'=>'number',
                        'name'=> $name,
                        'value' => $value
                    ));
                    echo CHtml::tag('input',$inputHtmlOptions,'',false);
                break;

                case 'textarea':
                    echo CHtml::textArea($name, $value, $inputHtmlOptions);
                break; 

                case 'select':
                    $listData = is_string($this->listData) ? 
                        $this->evaluateExpression($this->listData,array('data'=>$data,'row'=>$row)) :
                        $this->listData;
                    echo CHtml::dropDownList($name, $value, $listData,$inputHtmlOptions);
                break;  

                case 'checkbox':
                    $listData = is_string($this->listData) ? 
                        $this->evaluateExpression($this->listData,array('data'=>$data,'row'=>$row)) :
                        $this->listData;
                    echo CHtml::checkBox($name, $value, $inputHtmlOptions);
                break;  

                case 'checkboxlist':
                    $listData = is_string($this->listData) ? 
                        $this->evaluateExpression($this->listData,array('data'=>$data,'row'=>$row)) :
                        $this->listData;
                    echo CHtml::checkBoxList($name, $value, $listData,$inputHtmlOptions);
                break;  

                case 'radiobuttonlist':
                    $listData = is_string($this->listData) ? 
                        $this->evaluateExpression($this->listData,array('data'=>$data,'row'=>$row)) :
                        $this->listData;
                    echo CHtml::radioButtonList($name, $value, $listData,$inputHtmlOptions);
                break; 

                case 'display':
                    echo $value;
                break; 

                case 'file':
                    echo CHtml::fileField($name, $value, $inputHtmlOptions);
                break;    

                default:
                    echo CHtml::textField($name, $value, $inputHtmlOptions);
            }
        }
        if ($this->htmlAfterInput && is_string($this->htmlAfterInput))
            echo $this->evaluateExpression($this->htmlAfterInput,array('data'=>$data,'row'=>$row));
	}
    
    protected function resolveData($model) {
        $relations = explode('.',$this->name);
        $attr = array_pop($relations);
        foreach($relations as $name)  { 
            if(is_object($model)) 
                $model=$model->$name; 
            else if(is_array($model) && isset($model[$name])) 
                $model=$model[$name]; 
        } 
        return array($model,$attr);
    }
    
}

