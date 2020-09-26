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
		$this->RegisterPropertyInteger("NextSceneInterval",0);
		$this->RegisterPropertyBoolean("RepeatOnLastScene",false);
		$this->RegisterPropertyInteger("TransitionStepInterval",0);
		$this->RegisterPropertyInteger("TransitionSteps",0);
		$this->RegisterPropertyString("Scenes","");
		
		// Variable profiles
		$variableProfileSceneControl = "SCENESWITCH.SceneControl";
		if (IPS_VariableProfileExists($variableProfileSceneControl) ) {
		
			IPS_DeleteVariableProfile($variableProfileSceneControl);
		}			
		IPS_CreateVariableProfile($variableProfileSceneControl, 1);
		IPS_SetVariableProfileIcon($variableProfileSceneControl, "Remote");
		IPS_SetVariableProfileAssociation($variableProfileSceneControl, 1, "Next Scene", "HollowDoubleArrowRight", -1);
		
		// Variables
		$this->RegisterVariableBoolean("Status","Status","~Switch");
		$this->RegisterVariableBoolean("TransitionStatus","Transition in progress","~Switch");
		$this->RegisterVariableInteger("SceneNumber","Active Scene Number");
		$this->RegisterVariableInteger("TransitionStepNumber","Transition Step Number");
		IPS_SetHidden($this->GetIDForIdent("TransitionStepNumber"), true);
		$this->RegisterVariableInteger("SceneControl","Scene Control",$variableProfileSceneControl);
		$this->RegisterVariableString("SceneName","Active Scene Name");
		$this->RegisterVariableString("Transition","Scene Transition","~HTMLBox");
		$this->RegisterVariableString("TransitionJSON","Scene Transition JSON");
		IPS_SetHidden($this->GetIDForIdent("TransitionJSON"), true);
		
		// Default Actions
		$this->EnableAction("Status");
		$this->EnableAction("SceneControl");
		
		// Timer
		$this->RegisterTimer("RefreshInformation", 0 , 'SCENESWITCH_RefreshInformation($_IPS[\'TARGET\']);');
		$this->RegisterTimer("NextScene", 0 , 'SCENESWITCH_NextScene($_IPS[\'TARGET\']);');
		$this->RegisterTimer("NextTransitionStep", 0 , 'SCENESWITCH_NextTransitionStep($_IPS[\'TARGET\']);');
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
		$form['elements'][] = Array("type" => "Label", "name" => "HeadingGlobal", "caption" => "Global Settings");
		$form['elements'][] = Array("type" => "NumberSpinner", "name" => "RefreshInterval", "caption" => "Refresh Interval");
		$form['elements'][] = Array("type" => "CheckBox", "name" => "DebugOutput", "caption" => "Enable Debug Output");
		$form['elements'][] = Array("type" => "Label", "name" => "HeadingTarget", "caption" => "Target Device");
		$form['elements'][] = Array("type" => "SelectVariable", "name" => "TargetStatusVariableId", "caption" => "Target Status Variable");
		$form['elements'][] = Array("type" => "SelectVariable", "name" => "TargetIntensityVariableId", "caption" => "Target Intensity Variable");
		$form['elements'][] = Array("type" => "SelectVariable", "name" => "TargetColorVariableId", "caption" => "Target Color Variable");
		$form['elements'][] = Array("type" => "Label", "name" => "HeadingScenes", "caption" => "Scene Settings");
		$form['elements'][] = Array("type" => "NumberSpinner", "name" => "NextSceneInterval", "caption" => "Switch Scene Interval");
		$form['elements'][] = Array("type" => "CheckBox", "name" => "RepeatOnLastScene", "caption" => "Repeat after last Scene");
		$form['elements'][] = Array("type" => "NumberSpinner", "name" => "TransitionSteps", "caption" => "Number of Steps for each transition");
		$form['elements'][] = Array("type" => "NumberSpinner", "name" => "TransitionStepInterval", "caption" => "Interval between transition steps");
		
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
		$form['actions'][] = Array(	"type" => "Button", "label" => "Turn On", "onClick" => 'SCENESWITCH_TurnOn($id);');
		$form['actions'][] = Array(	"type" => "Button", "label" => "Next Scene", "onClick" => 'SCENESWITCH_NextScene($id);');
		$form['actions'][] = Array(	"type" => "Button", "label" => "Next Transition Step", "onClick" => 'SCENESWITCH_NextTransitionStep($id);');

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
			case "SceneControl":
				if ($Value == 1) {
					$this->NextScene();
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
		
		SetValue($this->GetIDForIdent("SceneNumber"), 0);
		SetValue($this->GetIDForIdent("SceneName"), "");
		
		SetValue($this->GetIDForIdent("TransitionStatus"), false);
		SetValue($this->GetIDForIdent("TransitionStepNumber"), 0);
		SetValue($this->GetIDForIdent("Transition"), "");
		SetValue($this->GetIDForIdent("TransitionJSON"), "");
		
		$this->SetTimerInterval("NextScene", 0);
		$this->SetTimerInterval("NextTransitionStep", 0);
	}
	
	public function TurnOn() {
		
		SetValue($this->GetIDForIdent("Status"), true);
		$this->ActivateSceneNumber(1);
		
		$newInterval = $this->ReadPropertyInteger("NextSceneInterval") * 1000;
		$this->SetTimerInterval("NextScene", $newInterval);

	}
	
	public function ActivateSceneNumber(int $sceneNumber) {
		
		if ($sceneNumber <= 0) {
			
			$this->LogMessage("Scene number must be positive. You specified " . $sceneNumber,"ERROR");
			return;
		}
		
		if ($sceneNumber > $this->GetNumberOfScenes() ) {
			
			$this->LogMessage("Scene number refers to a scene that does not exist. You specified " . $sceneNumber . " but there are only " . $this->GetNumberOfScenes() . " scenes defined","ERROR");
			return;
		}
		
		$scene = $this->GetScene($sceneNumber);
		
		if (! $scene) {
			
				$this->LogMessage("Unable to retrieve scene definition", "ERROR");
				return;
		}
		
		// This is a fresh turn on 
		if (GetValue($this->GetIDForIdent("SceneNumber")) == 0) {
		
			$this->SetTargetDevice($scene);
			
			SetValue($this->GetIDForIdent("Transition"), "");
			SetValue($this->GetIDForIdent("TransitionJSON"), "");
			SetValue($this->GetIDForIdent("SceneNumber"), $scene['Number']);
			SetValue($this->GetIDForIdent("SceneName"), $scene['Name']);

			
			return;
		}
		
		// No Transition Steps are needed in this case
		if ($this->ReadPropertyInteger("TransitionSteps") <= 1) {
			
			$scene = $this->GetScene($sceneNumber);
			$this->SetTargetDevice($scene);
			
			SetValue($this->GetIDForIdent("Transition"), "");
			SetValue($this->GetIDForIdent("TransitionJSON"), "");
			
			SetValue($this->GetIDForIdent("SceneNumber"), $scene['Number']);
			SetValue($this->GetIDForIdent("SceneName"), $scene['Name']);
			
			return;
		}
		else {
			
			$this->CalculateTransition();
			
			SetValue($this->GetIDForIdent("TransitionStatus"), true);
			SetValue($this->GetIDForIdent("TransitionStepNumber"), 1);
			
			$this->RenderTransitionToHtml();
			
			$currentStep = $this->GetTransitionStep(1);
			$this->SetTargetDevice($currentStep);
			
			$newInterval = $this->ReadPropertyInteger("TransitionStepInterval") * 1000;
			$this->SetTimerInterval("NextTransitionStep", $newInterval);
			
			SetValue($this->GetIDForIdent("SceneNumber"), $scene['Number']);
			SetValue($this->GetIDForIdent("SceneName"), $scene['Name']);
		}
	}
	
	public function NextTransitionStep() {
		
		if (! GetValue($this->GetIDForIdent("TransitionStatus")) ) {
			
			$this->LogMessage("Cannot proceed to next transition step as no transition is in progress","DEBUG");
			return false;
		}
		
		$currentTransitionStepNumber = GetValue($this->GetIDForIdent("TransitionStepNumber"));
		
		$nextTransitionStepNumber = $currentTransitionStepNumber + 1;
		
		// Abort if out of bounds
		if ($nextTransitionStepNumber > $this->GetNumberOfTransitions() ) {
			
			$this->LogMessage("Cannot proceed transition as the current step is out of bounds. deactivating transitions","DEBUG");
			
			SetValue($this->GetIDForIdent("TransitionStatus"), false);
			SetValue($this->GetIDForIdent("TransitionStepNumber"), 0);
			SetValue($this->GetIDForIdent("Transition"), "");
			SetValue($this->GetIDForIdent("TransitionJSON"), "");
			
			$this->SetTimerInterval("NextTransitionStep", 0);
			
			return;
		}
		
		$transition = $this->GetTransitionStep($nextTransitionStepNumber);
		if (! $transition) {
			
			$this->LogMessage("Unable to get Transition information for step " . $nextTransitionStepNumber, "ERROR");
			return;
		}
		
		$this->SetTargetDevice($transition);
		
		// End if on last step
		if ($nextTransitionStepNumber == $this->GetNumberOfTransitions() ) {
			
			SetValue($this->GetIDForIdent("TransitionStatus"), false);
			SetValue($this->GetIDForIdent("TransitionStepNumber"), 0);
			SetValue($this->GetIDForIdent("Transition"), "");
			SetValue($this->GetIDForIdent("TransitionJSON"), "");
			
			$this->SetTimerInterval("NextTransitionStep", 0);
		}
		else {
			
			SetValue($this->GetIDForIdent("TransitionStepNumber"), $nextTransitionStepNumber);
			$this->RenderTransitionToHtml();
		}
	}
		
	protected function SetTargetDevice($scene) {
		
		// We check first if the device needs to be turned off. If this is the case we execute this immediately, then stop
		if (! $scene['Status']) {
			
			if (GetValue($this->ReadPropertyInteger("TargetStatusVariableId"))) {
				
				$this->LogMessage("Scene requests device to be turned off but it is on. Turning it off","DEBUG");
				RequestAction($this->ReadPropertyInteger("TargetStatusVariableId"), false);
				return;
			}
		}
		
		// Setting the color also sets the intensity and turns the device on is needed. So we do this next.
		if ($scene['Color']) {
			
			if ($this->ReadPropertyInteger("TargetColorVariableId")) {
				
				if ($scene['Color'] != GetValue($this->ReadPropertyInteger("TargetColorVariableId")) ) {
					
					$this->LogMessage("Adjusting Color to value " . $scene['Color'], "DEBUG");
					RequestAction($this->ReadPropertyInteger("TargetColorVariableId"), $scene['Color']);
				}
			}
			else {
				
				$this->LogMessage("Scene asks for a color to be set but no Color Variable was defined in the instance configuration","ERROR");
			}
		}
		
		// If no color was defined we proceed with intensity
		if ($scene['Intensity']) {
			
			if ($this->ReadPropertyInteger("TargetIntensityVariableId")) {
				
				if ($scene['Intensity'] != GetValue($this->ReadPropertyInteger("TargetIntensityVariableId")) ) {
					
					$this->LogMessage("Adjusting Intensity to value " . $scene['Intensity'], "DEBUG");
					RequestAction($this->ReadPropertyInteger("TargetIntensityVariableId"), $scene['Intensity']);
				}
			}
			else {
				
				$this->LogMessage("Scene asks for intensity to be set but no Intensity Variable was defined in the instance configuration","ERROR");
			}
		}
		
		
		// Last option: Turn it on
		if ($scene['Status']) {
			
			if (GetValue($this->ReadPropertyInteger("TargetStatusVariableId"))) {
				
				$this->LogMessage("Scene requests device to be turned on but it is off. Turning it on","DEBUG");
				RequestAction($this->ReadPropertyInteger("TargetStatusVariableId"), true);
			}
		}
	}
	
	public function GetNumberOfScenes() {
		
		$scenesJson = $this->ReadPropertyString("Scenes");
		$scenes = json_decode($scenesJson);
		
		if (! is_array($scenes)) {
			
			return 0;
		}
		else {
			
			return count($scenes);
		}
	}
	
	public function GetNumberOfTransitions() {
		
		$transitionJson = GetValue($this->GetIDForIdent("TransitionJSON"));
		$transition = json_decode($transitionJson, true);
		
		if (! is_array($transition)) {
			
			return 0;
		}
		else {
			
			return count($transition) - 1;
		}
	}
	
	public function NextScene() {
		
		if (! GetValue($this->GetIDForIdent("Status"))) {
			
			$this->LogMessage("SceneSwitcher is not active. Unable to switch to next scene","ERROR");
			return;
		}
		
		$nextScene = GetValue($this->GetIDForIdent("SceneNumber")) + 1;
		
		if ($nextScene > $this->GetNumberOfScenes()) {
			
			if ($this->ReadPropertyBoolean("RepeatOnLastScene")) {
				
				$this->LogMessage("Already at last scene. Restarting at 1 as repeat is active", "DEBUG");
				$this->ActivateSceneNumber(1);
			}
			else {
			
				$this->LogMessage("Already at last scene. Turning off as repeat is inactive", "DEBUG");
				$this->TurnOff();
			}
		}
		else {
			
			$this->LogMessage("Switching to next Scene $nextScene", "DEBUG");
			$this->ActivateSceneNumber($nextScene);
		}
	}
	
	public function GetCurrentScene() {
		
		if (! GetValue($this->GetIDForIdent("Status"))) {
			
			return false;
		}
			
		$sceneNumber = GetValue($this->GetIDForIdent("SceneNumber"));
		
		return $this->GetScene($sceneNumber);
	}
	
	public function GetNextScene() {
		
		if (! GetValue($this->GetIDForIdent("Status"))) {
			
			return false;
		}
		
		$currentSceneNumber = GetValue($this->GetIDForIdent("SceneNumber"));
		$sceneNumber = $currentSceneNumber + 1;
		
		if ($sceneNumber > $this->GetNumberOfScenes() ) {
			
			if ($this->ReadPropertyBoolean("RepeatOnLastScene")) {
				
				$sceneNumber = 1;
			}
			else {
				
				return false;
			}
		}
		
		return $this->GetScene($sceneNumber);
	}
	
	protected function GetScene($sceneNumber) {
		
		$scenesJson = $this->ReadPropertyString("Scenes");
		$scenes = json_decode($scenesJson);
		
		if (! is_array($scenes)) {
			
			$this->LogMessage("No Scenes are defined. Unable to Get Scene Details","ERROR");
			return;
		}
		
		if (count($scenes) == 0) {
			
			$this->LogMessage("No Scenes are defined. Unable to Get Scene Details","ERROR");
			return;
		}
		
		if ($sceneNumber <= 0) {
			
			$this->LogMessage("Scene number must be positive. You specified " . $sceneNumber,"ERROR");
			return;
		}
		
		if ($sceneNumber > $this->GetNumberOfScenes() ) {
			
			$this->LogMessage("Scene number refers to a scene that does not exist. You specified " . $sceneNumber . " but there are only " . $this->GetNumberOfScenes() . " scenes defined","ERROR");
			return;
		}
		
		$sceneIndex  = $sceneNumber - 1;
		
		$currentScene = Array(
			"Status" => $scenes[$sceneIndex]->Status,
			"Intensity" => $scenes[$sceneIndex]->Intensity,
			"Color" => $scenes[$sceneIndex]->Color,
			"Name" => $scenes[$sceneIndex]->Name,
			"Number" => $sceneNumber
		);
		
		return $currentScene;
	}
	
	protected function GetTransitionStep($stepNumber) {
		
		$transitionJson = GetValue($this->GetIDForIdent("TransitionJSON"));
		$transition = json_decode($transitionJson, true);
		
		if (! is_array($transition)) {
			
			$this->LogMessage("No Transitions are defined. Unable to Get Transition Details","ERROR");
			return;
		}
		
		if (count($transition) == 0) {
			
			$this->LogMessage("No Transitions are defined. Unable to Get Transition Details","ERROR");
			return;
		}
		
		if ($stepNumber <= 0) {
			
			$this->LogMessage("Step number must be positive. You specified " . $stepNumber,"ERROR");
			return;
		}
		
		if ($stepNumber > count($transition) ) {
			
			$this->LogMessage("Step number refers to a Transition step that does not exist. You specified " . $stepNumber . " but there are only " . count($transition) . " steps defined","ERROR");
			return;
		}
		
		$currentStep = Array(
			"Status" => $transition[$stepNumber]['Status'],
			"Intensity" => $transition[$stepNumber]['Intensity'],
			"Color" => $transition[$stepNumber]['Color']
		);
		
		return $currentStep;
	}
	
	protected function CalculateTransition() {
		
		$currentScene = $this->GetCurrentScene();
		$nextScene = $this->GetNextScene();
		
		if (! $nextScene) {
			
			SetValue($this->GetIDForIdent("Transition"), "This is the last scene. No transition is needed.");
			return;
		}
		
		$transitionSteps = $this->ReadPropertyInteger("TransitionSteps");
		
		$deltaIntensity = $nextScene['Intensity'] - $currentScene['Intensity'];
		$stepsizeIntensity = $deltaIntensity / $transitionSteps;
		
		$currentSceneColorRed = (($currentScene['Color'] >> 16) & 0xFF); 
		$currentSceneColorGreen=(($currentScene['Color'] >> 8) & 0xFF); 
		$currentSceneColorBlue=(($currentScene['Color']) & 0xFF); 

		$nextSceneColorRed = (($nextScene['Color'] >> 16) & 0xFF); 
		$nextSceneColorGreen=(($nextScene['Color'] >> 8) & 0xFF); 
		$nextSceneColorBlue=(($nextScene['Color']) & 0xFF); 
		
		$deltaColorRed = $nextSceneColorRed - $currentSceneColorRed;
		$deltaColorGreen = $nextSceneColorGreen - $currentSceneColorGreen;
		$deltaColorBlue = $nextSceneColorBlue - $currentSceneColorBlue;
			
		$transition[0]['Status'] = $currentScene['Status'];
		$transition[0]['Intensity'] = $currentScene['Intensity'];
		$transition[0]['Color'] = $currentScene['Color'];
		if ($this->ReadPropertyBoolean('DebugOutput')) {
		
			$transition[0]['Color_Red'] = $currentSceneColorRed;
			$transition[0]['Color_Green'] = $currentSceneColorGreen;
			$transition[0]['Color_Blue'] = $currentSceneColorBlue;
		}
			
		for ($i=1; $i < $transitionSteps; $i++) {
			
			if ($currentScene['Status'] && $nextScene['Status']) {
				
				$transition[$i]['Status'] = true;
			}
			
			if ( (! $currentScene['Status']) && (! $nextScene['Status']) ) {
				
				$transition[$i]['Status'] = false;
			}
			
			if ( (! $currentScene['Status']) && $nextScene['Status']) {
				
				$transition[$i]['Status'] = false;
			}
			
			if ($currentScene['Status'] && (! $nextScene['Status']) ) {
				
				$transition[$i]['Status'] = true;
			}
			
			$transition[$i]['Intensity'] = round($currentScene['Intensity'] + ($stepsizeIntensity * $i) );
			
			$factor = $i / $transitionSteps;
			
			$transitionColorRed = round($currentSceneColorRed + $deltaColorRed * $factor);
			$transitionColorGreen = round($currentSceneColorGreen + $deltaColorGreen * $factor);
			$transitionColorBlue = round($currentSceneColorBlue + $deltaColorBlue * $factor);
			
			$transitionColorRedHex = $transitionColorRed << 16;
			$transitionColorGreenHex = $transitionColorGreen << 8;
			$transitionColorBlueHex = $transitionColorBlue;
			$transitionColorHex = $transitionColorRedHex + $transitionColorGreenHex + $transitionColorBlueHex;
			
			$transition[$i]['Color'] = $transitionColorHex;
			if ($this->ReadPropertyBoolean('DebugOutput')) {
		
				$transition[$i]['Color_Red'] = $transitionColorRed;
				$transition[$i]['Color_Green'] = $transitionColorGreen;
				$transition[$i]['Color_Blue'] = $transitionColorBlue;
			}
		}

		$transition[$transitionSteps]['Status'] = $nextScene['Status'];
		$transition[$transitionSteps]['Intensity'] = $nextScene['Intensity'];
		$transition[$transitionSteps]['Color'] = $nextScene['Color'];	
		if ($this->ReadPropertyBoolean('DebugOutput')) {
		
			$transition[$transitionSteps]['Color_Red'] = $nextSceneColorRed;
			$transition[$transitionSteps]['Color_Green'] = $nextSceneColorGreen;
			$transition[$transitionSteps]['Color_Blue'] = $nextSceneColorBlue;
		}
		
		SetValue($this->GetIDForIdent("TransitionJSON"), json_encode($transition));
		
	}
	
	protected function RenderTransitionToHtml() {
		
		$transition = json_decode(GetValue($this->GetIDForIdent("TransitionJSON")), true);
		
		$htmlText = '<table border="1px">' .
						'<thead>' .
							'<th>Step</th>' .
							'<th>Status</th>'  .
							'<th>Intensity</th>' .
							'<th>Color</th>' .
						'</thead>';
						
		$htmlText .= '<tbody>';
		
		for ($i=0; $i < count($transition); $i++) {
			
			if ($transition[$i]['Status']) {
				$transitionStatus = "On";
			}
			else {
				$transitionStatus = "Off";
			}
			
			$colorDecRed = (($transition[$i]['Color'] >> 16) & 0xFF);
			$colorDecGreen = (($transition[$i]['Color'] >> 8) & 0xFF);
			$colorDecBlue = (($transition[$i]['Color']) & 0xFF);
		
			$colorHexRed = dechex($colorDecRed);
			$colorHexGreen = dechex($colorDecGreen);
			$colorHexBlue = dechex($colorDecBlue);
			
			$colorHex = sprintf("#%2c%2c%2c", $colorHexRed, $colorHexGreen, $colorHexBlue);
			
			if ( (GetValue($this->GetIDForIdent("TransitionStepNumber")) == $i) && GetValue($this->GetIDForIdent("TransitionStatus") ) ) {
				
				$bgcolor_step = "red";
			}
			else {
				
				$bgcolor_step = "transparent";
			}
			
			$htmlText .= '<tr>' .
							'<td bgcolor="' . $bgcolor_step . '">' .
								$i .
							'</td>' .
							'<td>' .
								$transitionStatus .
							'</td>' .
							'<td>' .
								$transition[$i]['Intensity'] .
							'</td>' .
							'<td bgcolor="' . $colorHex . '">' .
								$transition[$i]['Color'] . ' / ' . $colorHex .
							'</td>' .
						'</tr>';
		}
		
		$htmlText .= '</tbody></table>';
		
		SetValue($this->GetIDForIdent("Transition"), $htmlText);
	}
}
