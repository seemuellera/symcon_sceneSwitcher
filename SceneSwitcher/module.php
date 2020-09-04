<?php

// Klassendefinition
class SceneSwitcher extends IPSModule {
 
	// Der Konstruktor des Moduls
	// Überschreibt den Standard Kontruktor von IPS
	public function __construct($InstanceID) {
		// Diese Zeile nicht löschen
		parent::__construct($InstanceID);

		// Selbsterstellter Code
	}

	// Überschreibt die interne IPS_Create($id) Funktion
	public function Create() {
		
		// Diese Zeile nicht löschen.
		parent::Create();

		// Properties
		$this->RegisterPropertyString("Sender","SceneSwitcher");
		$this->RegisterPropertyInteger("RefreshInterval",0);
		$this->RegisterPropertyBoolean("DebugOutput",false);
		$this->RegisterPropertyInteger("TargetStatusVariableId",0);
		$this->RegisterPropertyInteger("TargetIntensityVariableId",0);
		$this->RegisterPropertyInteger("TargetColorVariableId",0);
		$this->RegisterPropertyString("Scenes","");
		
		// Variables
		$this->RegisterVariableBoolean("Status","Status","~Switch");
		
		// Default Actions
		$this->EnableAction("Status");
		
		// Timer
		$this->RegisterTimer("RefreshInformation", 0 , 'SCENESWITCH_RefreshInformation($_IPS[\'TARGET\']);');
    }

	public function Destroy() {

		// Never delete this line
		parent::Destroy();
	}
 
	// Überschreibt die intere IPS_ApplyChanges($id) Funktion
	public function ApplyChanges() {

		$newInterval = $this->ReadPropertyInteger("RefreshInterval") * 1000;
		$this->SetTimerInterval("RefreshInformation", $newInterval);
		
		// Diese Zeile nicht löschen
		parent::ApplyChanges();
	}


	public function GetConfigurationForm() {
        	
		// Initialize the form
		$form = Array(
            		"elements" => Array(),
					"actions" => Array()
        		);

		// Add the Elements
		$form['elements'][] = Array("type" => "NumberSpinner", "name" => "RefreshInterval", "caption" => "Refresh Interval");
		$form['elements'][] = Array("type" => "CheckBox", "name" => "DebugOutput", "caption" => "Enable Debug Output");
		$form['elements'][] = Array("type" => "SelectVariable", "name" => "TargetStatusVariableId", "caption" => "Target Status Variable");
		$form['elements'][] = Array("type" => "SelectVariable", "name" => "TargetIntensityVariableId", "caption" => "Target Intensity Variable");
		$form['elements'][] = Array("type" => "SelectVariable", "name" => "TargetColorVariableId", "caption" => "Target Color Variable");
		
		$sceneColumns = Array(
			Array(
				"caption" => "Scene Name",
				"name" => "Name",
				"width" => "200px",
				"edit" => Array("type" => "ValidationTextBox"),
				"add" => "unnamed"
			),
			Array(
				"caption" => "Device Status",
				"name" => "Status",
				"width" => "100px",
				"edit" => Array("type" => "CheckBox"),
				"add" => true
			),
			Array(
				"caption" => "Device Intensity",
				"name" => "Intensity",
				"width" => "150px",
				"edit" => Array("type" => "NumberSpinner"),
				"add" => 100
			),
			Array(
				"caption" => "Device Color",
				"name" => "Color",
				"width" => "150px",
				"edit" => Array("type" => "ValidationTextBox"),
				"add" => ""
			)
		);
		$form['elements'][] = Array(
			"type" => "List", 
			"columns" => $sceneColumns, 
			"name" => "Scenes", 
			"caption" => "Scene List", 
			"add" => true, 
			"delete" => true,
			"rowCount" => 10
		);
		
		// Add the buttons for the test center
		$form['actions'][] = Array(	"type" => "Button", "label" => "Refresh", "onClick" => 'SCENESWITCH_RefreshInformation($id);');
		$form['actions'][] = Array(	"type" => "Button", "label" => "Turn Off", "onClick" => 'SCENESWITCH_TurnOff($id);');

		// Return the completed form
		return json_encode($form);

	}
	
	protected function LogMessage($message, $severity = 'INFO') {
		
		if ( ($severity == 'DEBUG') && ($this->ReadPropertyBoolean('DebugOutput') == false )) {
			
			return;
		}
		
		$messageComplete = $severity . " - " . $message;
		
		IPS_LogMessage($this->ReadPropertyString('Sender'), $messageComplete);
	}

	public function RefreshInformation() {

		$this->LogMessage("Refresh in Progress", "DEBUG");
	}

	public function RequestAction($Ident, $Value) {
	
	
		switch ($Ident) {
		
			case "Status":
				if ($Value) {
				
					$this->TurnOn();
				}
				else {
					
					$this->TurnOff();
				}
				break;
			default:
				throw new Exception("Invalid Ident");
		}
	}
	
	public function TurnOff() {
		
		$this->LogMessage("Turning device off","DEBUG");
		RequestAction($this->ReadPropertyInteger("TargetStatusVariableId"), false);
		SetValue($this->GetIDForIdent("Status"), false);
	}
	
	public function TurnOn() {
		
		$scenesJson = $this->ReadPropertyString("Scenes");
		$scenes = json_decode($scenesJson);
		
		if (! is_array($scenes)) {
			
			$this->LogMessage("No Scenes are defined. Unable to turn on SceneSwitcher","ERROR");
			return;
		}
	}

}
