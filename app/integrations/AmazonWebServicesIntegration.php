<?php
use Aws\Iam\IamClient;

class AmazonWebServicesIntegration extends CloudIntegration
{
	public $fields = [['access_key_id', 'Access Key ID'], ['secret_access_key', 'Secret Access Key']];
	
	public $description = '<p>This IAM user must have access to EC2. NoSprawl will perform the following operations:</p><ul><li>Getting list of EC2 instances</li><li>Get list of Base Images</li><li>Get list of Elastic Beanstalk Clusters</li></ul></ul>';
	
	public $db_integration_id;
	
	public $availability_zones = array(
		"ap-northeast-1" => "Tokyo",
		"ap-southeast-1" => "Singapore",
		"ap-southeast-2" => "Sydney",
		"eu-central-1" => "Frankfurt",
		"eu-west-1" => "Ireland",
		"sa-east-1" => "Sao Paulo",
		"us-east-1" => "N. Virginia",
		"us-west-1" => "N. California",
		"us-west-2" => "Oregon"
	);
	
	public $lat_long_index = array(
		"ap-northeast-1" => array("lat" => 35.689487, "lon" => 139.691706),
		"ap-southeast-1" => array("lat" => 1.352083, "lon" => 103.819836),
		"ap-southeast-2" => array("lat" => -33.867487, "lon" => 151.206990),
		"eu-central-1" => array("lat" => 50.110922, "lon" => 8.682127),
		"eu-west-1" => array("lat" => 53.412910, "lon" => -8.243890),
		"sa-east-1" => array("lat" => -23.550520, "lon" => -46.633309),
		"us-east-1" => array("lat" => 37.431573, "lon" => -78.656894),
		"us-west-1" => array("lat" => 43.804133, "lon" => -120.554201),
	);
	
	public function verifyAuthentication($access_key_id, $secret_access_key) {
		$success = false;
		try {
			$client = \Aws\Ec2\Ec2Client::factory(array('key' => $access_key_id, 'secret' => $secret_access_key, 'region' => 'us-east-1'));
			$res = $client->DescribeInstances();
			$success = true;
		} catch(Exception $exception) {
			
		}
		
		return $success;
	}
	
	public function list_nodes($availability_zone_name, $availability_zone_friendly_name) {
		#$output = new Symfony\Component\Console\Output\ConsoleOutput();
		#$output->writeln($availability_zone_name);
		$success = false;
		$integration = Integration::find($this->db_integration_id);
		$nodes = [];
		
		$client = \Aws\Ec2\Ec2Client::factory(array('key' => $integration->authorization_field_1, 'secret' => $integration->authorization_field_2, 'region' => $availability_zone_name));
		
		$res = $client->DescribeInstances();
		$reservations = $res['Reservations'];
		$success = [];
		
		#$output->writeln(print_r($reservations));
		
		foreach($reservations as $reservation) {
			$instances = $reservation['Instances'];
			foreach($instances as $instance) {
				$platform = "Linux";
				if(isset($instance['Platform'])) {
					$platform = ucfirst($instance['Platform']);
				}
				
				$interfaces = [];
				foreach($instance['NetworkInterfaces'] as $network_interface) {
					array_push($interfaces, $network_interface['MacAddress']);
				}
				
				// Find out if we're part of a cluster. This feature is being deprecated.
				$sp_cluster_id = null;
				try {
					foreach($instance['Tags'] as $tag) {
						if($tag['Key'] == 'elasticbeanstalk:environment-id') {
							$sp_cluster_id = $tag['Value'];
						}
				
					}
					
				} catch(Exception $e) {
				
				}
					
				$all_ips = array();
				
				foreach($instance['NetworkInterfaces'] as $ni) {
					foreach($ni['PrivateIpAddresses'] as $interface) {
						array_push($all_ips, (isset($interface['PrivateIpAddress'])) ? $interface['PrivateIpAddress'] : null, (isset($interface['Association']['PublicIp'])) ? $interface['Association']['PublicIp'] : null);
					}
					
				}
				
        array_push($nodes, array('service_provider_status' => $instance['State']['Name'],
																 'service_provider_base_image_id' => $instance['ImageId'],
																 'service_provider_id' => $instance['InstanceId'],
															   'private_dns_name' => $instance['PrivateDnsName'],
															   'public_dns_name' => $instance['PublicDnsName'],
															   'network_interfaces' => $interfaces,
															 	 'service_provider_cluster_id' => $sp_cluster_id,
																 'service_provider_ip_addresses' => $all_ips,
															 	 'availability_zone_friendly' => $availability_zone_friendly_name,
															   'availability_zone_name' => $availability_zone_name,
															 	 'platform' => $platform));
			}
			
		}
		
		return $nodes;
	}
	
}