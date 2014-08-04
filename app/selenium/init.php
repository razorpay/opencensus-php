<?php
//Performs tasks required when laravel is served by artisan for selenium

//Mock S3
	$s3 = Mockery::mock('overload:Aws\S3\S3Client');

    $s3->shouldReceive('putObject')->withAnyArgs()->andReturn(array('ObjectURL'=>'https://aws.com/yo.png'));

    $s3->shouldReceive('factory')->withAnyArgs()->andReturn($s3);

    App::instance('Aws\S3\S3Client', $s3);

//Enable Filters
	Route::enableFilters();
?>