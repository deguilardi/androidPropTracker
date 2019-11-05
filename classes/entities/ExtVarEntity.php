<?php
class ExtVarEntity{
	public $var;
	public $value;

	public function __construct( $var, $value ){
		$this->var = $var;
		$this->value = $value;
	}
}