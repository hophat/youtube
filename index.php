<?php
include "api_youtube.php";
$youtube = new Youtube();
$list_home = $youtube->getListHome();

?>

<!DOCTYPE html>
<html>
<head>
	<title>Youtube -  auto</title>
	

	<script src="assets/js/jwplayer.js"></script>
	<script type="text/javascript">
		jwplayer.key = "ITWMv7t88JGzI0xPwW8I0+LveiXX9SWbfdmt0ArUSyc=";
	</script>


	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
	<script
	src="https://code.jquery.com/jquery-3.4.1.js"
	integrity="sha256-WpOohJOqMqqyKL9FccASB9O0KwACQJpFTUBLTYOVvVU="
	crossorigin="anonymous"></script>

	
</head>
<body>

	<div class="container">
		<h1>LIST HOME YOUTUBE</h1>
		<div class="row">
			<div class="col-md-6" id="playvideo"></div>
			<?php 
			$id = 0;

			foreach ($list_home as $key) :
				$id++;
				?>
				<div class="col-md-4 col-12">
					<div class="card m-3" id="id<?=$id?>" 
						Onclick="get_play(<?=$id?>,`<?=$key['link']?>`)" >

						<img class="card-img-top" src="<?=$key['img']?>" alt="Card image" style="width:100%">

						<div class="card-body">
							<h6 class="card-title"><?=$key['title']?></h6>
						</div>
					</div>
				</div>
				<?php 
			endforeach;
			?>
		</div>
	</div>
</body>
</html>
<script type="text/javascript">
	async function get_play(id,link){

		var resf = fetch("get_link_play.php?link="+link,{"method":"GET"}).then(res=>{
			return res.json();
		});
		var json = await resf;
		// console.log(json);
		var list = json.list;
		console.log(list);
		// var playlist = list;
		// list.forEach(e=>{
		// 	// console.log(e.file)
		// 	playlist.push({'file':e.file,
		// })

		// })

		var playlistThree = [{
			"file":"//content.jwplatform.com/videos/RDn7eg0o-cIp6U8lV.mp4",
			"image":"//content.jwplatform.com/thumbs/RDn7eg0o-720.jpg",
			"title": "Surfing Ocean Wave"
		},{
			"file": "//content.jwplatform.com/videos/tkM1zvBq-cIp6U8lV.mp4",
			"image": "//content.jwplatform.com/thumbs/tkM1zvBq-720.jpg",
			"title": "Surfers at Sunrise"
		},{
			"file": "//content.jwplatform.com/videos/i3q4gcBi-cIp6U8lV.mp4",
			"image":"//content.jwplatform.com/thumbs/i3q4gcBi-720.jpg",
			"title": "Road Cycling Outdoors"
		}];

		// console.log(playlist);
		console.log(playlistThree);
		var player = jwplayer('playvideo');
		player.setup({
			width: '100%',
			aspectratio: '16:9',
			// file: list.file,
			
			sources:list, 
			// load:[{"file":list.file,"type":"video/mp4"},{"file":list.file,"type":"video/mp4"}],
			aboutlink: '',
			autostart: 'true',
			tracks: [{
				file: "",
				label: "Welcome",
				kind: "captions",
				"default": true
			}] 
		});
		player.load(list)

	}
</script>