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
    
    /**
     * @var string|array the type for the input cell. Available types are:
     * 1) "text"(default), "number", "textarea", "select", "checkbox", "checkboxlist", "radiobattonlist", "file" 
     * (They produce a corresponding <input type='...'> tag)
     * 2) "display" - just displays the cell value, 
     *    "none" - displays nothing
     * 3) configuration array for the input widget, for example:
     *    'inputType'=>array(
     *       'class'=>'ext.jqSpinner.jqSpinnerWidget', // 'class' is a obligatory parameter
     *       'htmlOptions'=>array(                     // widget config 
     *           'style'=>'width: 65px;',               
     *       ),                                         
     *       'options'=>array(
     *           'step'=>1,
     *       )                                         //
     *   ),
     * 
     */
    public $inputType = 'text';
    
    /**
     * @var string a PHP expression that is evaluated for every data cell using {@link evaluateExpression}
     * and whose result is used to define the type for the input cell.
     * Therefore in one table column could be presented a different types of inputs.
     * In expression, you can use the same variables as for CDataColumn ($row,$data and $this) 
     */
    public $inputTypeExpression;
    
    /**
     * @var array|string an array or a PHP expression that is evaluated for every data cell using {@link evaluateExpression} 
     * and whose result is used to get an array of data for the input types: "select","checkboxlist","radiobuttonlist".
     * This array will be used as $data parameter for the corresponding CHtml method (for example: CHtml::dropDownList(...,...,$data,...)) 
     * In expression, you can use the same variables as for CDataColumn ($row,$data and $this)
     */
    public $listData;
    
    /**
     * @var array the HTML options for the input tag.
     */
    public $inputHtmlOptions = array();
    
    /**
     * @var string the suffix which will be added to the input name
     */
    public $varSuffix = '';
    
    /**
     * @var string the attribute name that contains the primary key for the corresponding data model. Default is 'id'.
     */
    public $idAttribute = 'id';
    
    /**
     * @var string the inline styles which will be applied to input tag in case of validation error
     */
    public $errorStyle = '';
    
    /**
     * @var string the css class which will be applied to input tag in case of validation error
     */
    public $errorClass = 'error';
    
    /**
     * @var string a PHP expression that is evaluated for every data cell using {@link evaluateExpression}
     * and whose result is used to add and additional HTML before input tag
     */
    public $htmlBeforeInput;
    
    /**
     * @var string a PHP expression that is evaluated for every data cell using {@link evaluateExpression}
     * and whose result is used to add and additional HTML after input tag
     */
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
            $postVar = CHtml::modelName($data).$this->varSuffix;
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
                    if(!isset($inputHtmlOptions['uncheckValue']))
                        $inputHtmlOptions['uncheckValue'] = 0;
                    echo CHtml::checkBox($name, $value, $inputHtmlOptions);
                break;  

                case 'checkboxlist':
                    $listData = is_string($this->listData) ? 
                        $this->evaluateExpression($this->listData,array('data'=>$data,'row'=>$row)) :
                        $this->listData;
                    if(!isset($inputHtmlOptions['uncheckValue']))
                        $inputHtmlOptions['uncheckValue'] = 0;
                    echo CHtml::checkBoxList($name, $value, $listData,$inputHtmlOptions);
                break;  

                case 'radiobuttonlist':
                    $listData = is_string($this->listData) ? 
                        $this->evaluateExpression($this->listData,array('data'=>$data,'row'=>$row)) :
                        $this->listData;
                    echo CHtml::radioButtonList($name, $value, $listData,$inputHtmlOptions);
                break; 

                case 'file':
                    echo CHtml::fileField($name, $value, $inputHtmlOptions);
                break; 
            
                case 'display':
                    echo $value;
                break; 
            
                case 'none':
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

