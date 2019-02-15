<?php
	
	use Digitell\LiveEventsPlatformAPI\Sessions\BrowserAuth\IdentitySessionEntryBuilder;
	
	$client_id = 'example';
	$unique_id = '12345';
	
	$builder = new IdentitySessionEntryBuilder(
		$client_id,
		file_get_contents(__DIR__ . '/certificates/' . $client_id . '.pem')
	);
	
	$builder
		->setName('Joe Blogs')
		->setEmail('joe.blogs@exmaple.com')
		->setIdentifier($client_id . '\\' . $unique_id)
		->setSessionReference('abc-123456');
	
	echo $builder->toUrl();