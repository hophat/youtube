<?php
class Youtube
{
	private $key = [];
	private $sources = [];
	private $copyright = false;

	public function get($link)
	{
		$content = $this->curl($link);

		if(preg_match('#ytplayer\.config = (\{.+\});#U', $content, $matches))
		{
			$json = json_decode($matches[1]);

			$this->readJs('http://s.ytimg.com'.$json->assets->js);

			$url_encoded_fmt_stream_map = $json->args->url_encoded_fmt_stream_map;
			$url_encoded_fmt_stream_map = explode(',', $url_encoded_fmt_stream_map);
			$adaptive_fmts = $json->args->adaptive_fmts;
			$adaptive_fmts = explode(',', $adaptive_fmts);

			$streamMap = array_merge($url_encoded_fmt_stream_map, $adaptive_fmts);

			foreach ($streamMap as $url)
			{
				$redirector = '';

				$url = str_replace('\u0026', '&', $url);

				$url = urldecode($url);

				parse_str($url, $value);
				if(in_array($value['mime'],array('video/webm','audio/webm'))){
					continue;
				}

				if(isset($value['s']))
				{
					$value['url'] .= '&signature='.$this->decrypt($value['s']);

					$this->copyright = true;

					unset($value['s']);
				}
				else if($value['itag'] > 100 && isset($value['signature']) && $this->copyright)
				{
					$value['url'] .= '&signature='.$this->decrypt($value['signature']);

					unset($value['signature']);
				}
				// echo "<pre>";

				// var_dump($value);

				// unset($value['quality']);
				unset($value['codecs']);
				// unset($value['type']);
				unset($value['beids']);
				unset($value['projection_type']);
				unset($value['fps']);
				unset($value['init']);
				unset($value['index']);
				unset($value['size']);
				unset($value['xtags']);
				// unset($value['quality_label']);
				unset($value['bitrate']);

				$dataURL = $value['url'];

				unset($value['url']);

				$afterRedirector = str_replace('"', "'", $dataURL.'&'.urldecode(http_build_query($value)));
				$redirector = $afterRedirector;

				$this->sources[] = [
					'file' => $redirector,
					// 'type'=>"video/mp4",
					'title'=>$value['quality_label'],
					'type'=>$value['type']
					//'size' => $this->getSize($afterRedirector)
				];
			}
		}

		return $this->sources;
	}

	private function readJs($link)
	{
		$contents = $this->curl($link);

		preg_match('/\&\&(.*)\.set\("signature"\,(.*)\((.*)\)\)\;/U', $contents, $match);
		$function = $match[2];

		preg_match('#([A-Za-z0-9]+):function\(a\)\{a\.reverse\(\)\}#', $contents, $match);
		$method[$match[1]] = 'throw';

		preg_match('#([A-Za-z0-9]+):function\(a,b\)\{a\.splice\(0,b\)\}#', $contents, $match);
		$method[$match[1]] = 'half';

		preg_match('#([A-Za-z0-9]+):function\(a,b\)\{var c=a\[0\];a\[0\]=a\[b%a\.length\];a\[b\]=c\}#', $contents, $match);
		$method[$match[1]] = 'switch';

		preg_match('#'.str_replace('$', '\$', $function).'=function\(a\)\{a=a\.split\(\"\"\);([^\}]+)return a\.join\(\"\"\)\}#', $contents, $match);
		$contents = $match[1];

		preg_match_all('#[A-Za-z0-9]+\.([A-Za-z0-9]+)\(a,([0-9]+)\)#', $contents, $match);
		foreach($match[0] as $key => $temp)
		{
			$this->key[$key] = array
			(
				'method' => $method[$match[1][$key]],
				'value' => $match[2][$key]
			);
		}
	}

	private function decrypt($s)
	{
		foreach($this->key as $value)
		{
			if($value['method'] == 'switch')
			{
				$t = $s[0];
				$s[0] = $s[$value['value']%strlen($s)];
				$s[$value['value']] = $t;
			}
			else if($value['method'] == 'half')
			{
				$s = substr($s, $value['value']);
			}
			else if($value['method'] == 'throw')
			{
				$s = strrev($s);
			}
		}

		return $s;
	}

	private function getSize($url)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_NOBODY, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		$r = curl_exec($ch);
		foreach(explode("\n", $r) as $header)
		{
			if(strpos($header, 'Content-Length:') === 0)
			{
				return $this->formatBytes(trim(substr($header,16)));
			}
		}
		return '';
	}

	private function formatBytes($bytes, $precision = 2)
	{
		$units = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
		$bytes = max($bytes, 0);
		$pow = floor(($bytes ? log($bytes) : 0)/log(1024));
		$pow = min($pow, count($units) - 1);
		$bytes /= pow(1024, $pow);
		return round($bytes, $precision).''.$units[$pow];
	}

	private function curl($url)
	{
		$ch = @curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		$head[] = "Connection: keep-alive";
		$head[] = "Keep-Alive: 300";
		$head[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
		$head[] = "Accept-Language: en-us,en;q=0.5";
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/37.0.2062.124 Safari/537.36');
		curl_setopt($ch, CURLOPT_HTTPHEADER, $head);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_TIMEOUT, 60);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));
		$page = curl_exec($ch);
		curl_close($ch);
		return $page;
	}

	public function getListHome(){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'https://www.youtube.com/feed/trending?bp=4gIuCggvbS8wNHJsZhIiUExGZ3F1TG5MNTlhbW42X05FZFc5TGswZDdXZWVST0Q2VA%3D%3D');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
		// curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
		$headers = array();
		$headers[] = 'Authority: www.youtube.com';
		$headers[] = 'Pragma: no-cache';
		$headers[] = 'Cache-Control: no-cache';
		$headers[] = 'Upgrade-Insecure-Requests: 1';
		$headers[] = 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/75.0.3770.80 Safari/537.36';
		$headers[] = 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3';
		$headers[] = 'X-Client-Data: CKa1yQEIh7bJAQiltskBCMS2yQEIqZ3KAQioo8oBCPWkygEIsafKAQjiqMoBCPGpygE=';
		$headers[] = 'Referer: https://www.youtube.com/?app=desktop';
		$headers[] = 'Accept-Language: vi-VN,vi;q=0.9,fr-FR;q=0.8,fr;q=0.7,en-US;q=0.6,en;q=0.5';
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		$result = curl_exec($ch);
		if (curl_errno($ch)) {
			echo 'Error:' . curl_error($ch);
		}
		curl_close ($ch);
		preg_match("/ window\[\"ytInitialData\"] =.+/", $result, $matches);
		// echo "<pre>";
		$ok = str_replace('window["ytInitialData"] = ', "", $matches[0]);
		preg_match("/{\"sectionListRenderer\":{\"contents\":.+/", $ok, $matches);
		// echo "<pre>";
		$dd = explode(',"tabIdentifier"', $matches[0]);
		
		$content =json_decode($dd[0],TRUE) ;
		$array_l = $content['sectionListRenderer']['contents'][
			0]['itemSectionRenderer']['contents'][0]['shelfRenderer']['content']['expandedShelfContentsRenderer']['items'];
			$list_music_home = array();
			foreach ($array_l as $key => $value) {
				$link = 'https://www.youtube.com/watch?v='.$value['videoRenderer']['videoId'];
				$img = $value['videoRenderer']['thumbnail']['thumbnails'][0]['url'];
				$title = $value['videoRenderer']['title']['simpleText'];
				$video = compact('link','img','title');
				$list_music_home[] = $video;				
			}
			return $list_music_home;
		}
	}
	?>