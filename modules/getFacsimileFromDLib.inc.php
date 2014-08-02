<?php

// currently disabled
if(false && !file_exists($selectedWork['prefix']) && !file_exists('faksimili/'.$selectedWork['prefix'])) {

	set_time_limit(1800);
	flush();

	$PDFname = $selectedWork['file'].'_'.$selectedWork['dlib-urn'].'.pdf';
	if(!file_exists($PDFname)) {
		$dlib = file_get_contents('http://www.dlib.si/?URN=URN:NBN:SI:DOC-'.$selectedWork['dlib-urn']);
		$dlib = explode('PDF dokument', $dlib, 2);
		$dlib = explode('/stream/', $dlib[0], 2);
		$dlib = explode('"', $dlib[1], 2);
		$PDFurl = 'http://www.dlib.si/stream/'.$dlib[0];
		$PDFfile = file_get_contents($PDFurl);
		file_put_contents($PDFname, $PDFfile);
	}

	mkdir('extracted', 0774);
	exec('pdfimages -j '.$PDFname.' extracted\\extracted');

	// remove dLib logotype images
	foreach(scandir('extracted') as $file) {
		if(is_file('extracted/'.$file) && (filesize('extracted/'.$file) == 68474) && (md5_file('extracted/'.$file) == 'd0838a18dfe9db422ac44f4b30d85e6d')) {
			unlink('extracted/'.$file);
		}
	}

	exec('mogrify -format png extracted\\*.ppm');
	exec('del extracted\\*.ppm');
	exec('mogrify -format png extracted\\*.pbm');
	exec('del extracted\\*.pbm');

	$i = 1;
	foreach(scandir('extracted') as $file) {
		if(!is_file('extracted/'.$file)) {continue;}
		$ext = pathinfo($file, PATHINFO_EXTENSION);
		rename('extracted/'.$file, 'extracted/'.$selectedWork['prefix'].'-'.sprintf('%03d', $i).'.'.$ext);
		$i++;
	}

	rename('extracted', $selectedWork['prefix']);
	rename($PDFname, 'faksimili/'.$PDFname);

}
