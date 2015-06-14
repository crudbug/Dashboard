<?php

class ReportingController extends BaseController {
	protected $layout = 'layouts.front';
	
	public function __construct() {

	}

	public function reportingIndex() {
		$nodes = Auth::user()->nodes->groupBy('friendly_availability_zone');
		$magnitude_info = array();
		foreach($nodes as $availability_zone => $nodes) {
			foreach($nodes as $node) {
				if(!isset($magnitude_info[$node->friendly_availability_zone])) {
					$sp = new $node->integration->service_provider();
					$lat = $sp->lat_long_index[$node->service_provider_availability_zone]['lat'];
					$lon = $sp->lat_long_index[$node->service_provider_availability_zone]['lon'];
					$magnitude_info[$node->friendly_availability_zone] = array("magnitude" => 10, "lat" => $lat, "lon" => $lon, "sp_count" => array());
				} else {
					$magnitude_info[$node->friendly_availability_zone]['magnitude'] = $magnitude_info[$node->friendly_availability_zone]['magnitude'] + 10;
				}
				
				if(!isset($magnitude_info[$node->friendly_availability_zone]['sp_count'][$node->integration->service_provider])) {
					$magnitude_info[$node->friendly_availability_zone]['sp_count'][$node->integration->service_provider] = 1;
				} else {
					$magnitude_info[$node->friendly_availability_zone]['sp_count'][$node->integration->service_provider]++;
				}
				
			}
			
		}
		
		$this->layout->content = View::make('reporting.index')->with('magnitude_info', $magnitude_info);
	}

}