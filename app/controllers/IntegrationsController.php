<?php
require(dirname(__FILE__).'/../integrations/AmazonWebServicesIntegration.php');
require(dirname(__FILE__).'/../integrations/RackspaceCloudIntegration.php');

class IntegrationsController extends BaseController {
	protected $layout = 'layouts.front';
	
	public function __construct() {
		
	}

	public function getIntegrations() {
		$integrations = Auth::user()->integrations->toArray();
		$this->layout->content = View::make('integrations.list')->with("integrations", $integrations);
	}
	
	public function getFieldsForServiceProvider() {
		$input = Input::all();
		$integration_class_name = $input['service_provider_name'] . "Integration";
		$service_provider_class_instance = new $integration_class_name();
		return Response::json(array('service_provider_name' => $input['service_provider_name'],
																'service_provider_authorization_fields' => json_encode($service_provider_class_instance->fields),
																'service_provider_description' => json_encode($service_provider_class_instance->description)));
	}
	
	public function createIntegration() {
		$input = Input::all();
		$response = array("status" => "error", "data" => "");
		$rules = array();
		
		// Find out how many auth fields we are dealing with. One DB column is required for each.
		$authorization_field_count = 0;
		foreach(array_keys($input) as $field_name) {
			if(strstr($field_name, 'authorization_field_')) {
				$authorization_field_count++;
				$rules['authorization_field_' . $authorization_field_count] = 'required';
			}
			
		}
		
		$integration_class_name = $input['integration_type'] . "Integration";
		$service_provider_class_instance = new $integration_class_name();
									 
		$validator = Validator::make($input, $rules);
		if($validator->passes()) {
			$integration = new Integration;
			$integration->name = "";
			$integration->user_id = Auth::id();
			$integration->service_provider_id = $integration_class_name;
			
			for($i = 1; $i <= $authorization_field_count; $i++) {
				$dynamic_property_name = "authorization_field_" . $i;
				$integration->$dynamic_property_name = $input[$dynamic_property_name];
			}
			
			// This will dynamically get the integration class instance.
			$client = new $integration->service_provider_id();
			// Below line is the only place that is hardcoded to expect 2 auth fields.
			// @todo Need to refactor this to pass in a dynamic array and/or use call_user_func_array
			if($client->verifyAuthentication($integration->authorization_field_1, $integration->authorization_field_2)) {
				$integration->save();
				$integrationJson = $integration->toJson();
				Queue::push('ReauthenticateAndRefreshNodeList', array('message' => $integrationJson));
				$response['status'] = "created";
				$response['data'] = $integrationJson;
			} else {
				$response['status'] = "error";
				$response['data'] = "Could not list EC2 instances. NoSprawl requires at least read access to EC2.";
			}
			
			return Response::json($response);
		}
		
		return Response::json($response);
	}
	
	public function deleteIntegration($id) {
		Integration::destroy($id);
		return Redirect::to('/integrations');
	}

}