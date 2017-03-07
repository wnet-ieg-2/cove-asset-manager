//version 1 - DEPRECATED
(function ($) {
	$.fn.coveamDisplayPlayer = function() {
    var $divid = $(this).attr("id");
    var $showchrome = $(".coveam_playerchrome", this).text();
    var $coveplayer_chrome = "&topbar=true";
    var $youtube_chrome = "";
    var $autoplay = $(".coveam_autoplay", this).text();
    if ($showchrome == "hide") {
      $coveplayer_chrome = "&topbar=false";
      $youtube_chrome = "&controls=0";
    }
    var $coveplayer_autoplay = '&autoplay=false';
    var $youtube_autoplay = "";
    if ($autoplay == "autoplay") {
      $coveplayer_autoplay = '&autoplay=true';
      $youtube_autoplay = '&autoplay=1';
    }
	  var $covepartnerplayer_template = "<iframe class='partnerPlayer' frameborder='0' marginwidth='0' marginheight='0' scrolling='no' width='100%' height='100%' src='http://video.pbs.org/widget/partnerplayer/COVEPLAYERID/?start=0&end=0&chapterbar=false&endscreen=false" + $coveplayer_chrome + $coveplayer_autoplay +"'></iframe>";
	  var $youtubeplayer_template = "<iframe id='" + $divid + "_YouTubePlayer' width='100%' height='100%' src='//www.youtube.com/embed/YOUTUBEID?rel=0&showinfo=0&enablesjapi=1" + $youtube_chrome + $youtube_autoplay +"' frameborder='0' allowfullscreen></iframe>";
    var $videoid = $(".coveam_videoid", this).text();
	  var $coveplayerid = $(".coveplayerid", this).text();
	  var $youtubeid = $(".youtubeid", this).text();
	  var $covepreferred = $(".coveam_covepreferred", this).text();
	  var $videotitle = $(".coveam_videotitle", this).text();
	  var $videodescription = $(".coveam_videodescription", this).text();
	  var $airdate = $(".coveam_airdate", this).text();
	  var $this_coveplayer = $covepartnerplayer_template.replace('COVEPLAYERID', $coveplayerid);
	  var $this_youtubeplayer = $youtubeplayer_template.replace('YOUTUBEID', $youtubeid);
    var $this_alternateplayer = decodeURIComponent($(".coveam_video_override_encoded", this).text());
	  var $thisplayer;
    var $playertype = 'cove';
    if ($coveplayerid) {
      $thisplayer = $this_coveplayer;
      if (($covepreferred == "false") && $youtubeid) {
        $thisplayer = $this_youtubeplayer;
        $playertype = 'youtube';
      }
	  } else if ($youtubeid) {
      $thisplayer = $this_youtubeplayer;
      $playertype = 'youtube';
    } else if ($this_alternateplayer) {
      $thisplayer = $this_alternateplayer;
      $playertype = null;
    } else {
      $thisplayer = '<div class="errormessage">This video is not currently available.</div>';
      $playertype = null;
    }
	  $(".coveam_player", this).html($thisplayer);

    //find the first instance of any player and only fire the listeners for it
    $firstdiv = $(".video-wrapper").first().attr("id");
    if ($firstdiv == $divid) {
      if ($playertype == 'cove') {
         SetUpCoveListener();
      } else if ($playertype == 'youtube') {
         SetUpYouTubeAPI();
      }
    }
	  return this;
	};

  var playnexttimeout;
  //playnext screen
  function showPlayNextScreen() {
    if( ! $("html").hasClass("ie8") ) {
    var firstdiv = $(".video-wrapper").first().attr("id");
    firstdiv = "#" + firstdiv;
    var thisheight = $(firstdiv).height();
    var videoid_1 = $(firstdiv + " .coveam_relatedvid_id_1").text();
    var videotitle_1 = $(firstdiv + " .coveam_relatedvid_title_1").text();
    var videothumb_1 = $(firstdiv + " .coveam_relatedvid_img_1").text();
    var videopermalink_1 = $(firstdiv + " .coveam_relatedvid_permalink_1").text();
    var videoid_2 = $(firstdiv + " .coveam_relatedvid_id_2").text();
    var videotitle_2 = $(firstdiv + " .coveam_relatedvid_title_2").text();
    var videothumb_2 = $(firstdiv + " .coveam_relatedvid_img_2").text();
    var videopermalink_2 = $(firstdiv + " .coveam_relatedvid_permalink_2").text();
    var morechoices = '<div class="morechoices cf"><h4>Your video is over.  We would like to show you another one.</h4>';
    morechoices = morechoices + '<div class="leftchoice choice" style="float:left; width: 50%;"><h4>NEXT UP: in <span class="countdown">10</span> seconds <a href="#" id="coveam_pauser">PAUSE <i class="fa fa-pause"></i></a></h4><div class="choiceimage"><a href="' + videopermalink_1 + '"><img src="' + videothumb_1 + '" /></div><p><a href="' + videopermalink_1 + '">' + videotitle_1 + '</a></p></div>';
    morechoices = morechoices + '<div class="rightchoice choice" style="float:right; width: 50%;"><h4>You May Also Like:</h4><div class="choiceimage"><a href="' + videopermalink_2 + '"><img src="' + videothumb_2 + '" /></div><p><a href="' + videopermalink_2 + '">' + videotitle_2 + '</a></p></div></div>';
    if (videoid_1) {
      $(firstdiv + " .coveam_player").html(morechoices);
      $(firstdiv).height(thisheight);
      CountDownToNextVideo(firstdiv, 11);
    }
   }
  }

  function CountDownToNextVideo(div, num) {
    thisnum = Number(num);
    thisnum = thisnum - 1;
    if (thisnum > 0) {
      $(div + ' .countdown').text(thisnum);
      playnexttimeout = setTimeout(function() {
        CountDownToNextVideo(div, thisnum);
      }, 1000);
    } else {
      $(div).PlayNextVideo();
    }
  }

  $(".video-wrapper").on('click', 'a#coveam_pauser', function(e) {
    e.preventDefault();
    console.log('paused');
    clearTimeout(playnexttimeout);
  });

  $.fn.PlayNextVideo = function() {
    var thisid= $(this).attr("id");
    var num = 1;
    if (num != 2) { num = 1; }
    // first get the info for the selected vid.
    var videoid = $(".coveam_relatedvid_id_" + num, this).text();
    var coveplayerid = $(".coveam_relatedvid_coveplayerid_" + num, this).text();
    var youtubeid = $(".coveam_relatedvid_youtubeid_" + num, this).text();
    var video_override_encoded = $(".coveam_relatedvid_video_override_encoded_" + num, this).text();
    var videotitle = $(".coveam_relatedvid_title_" + num, this).text();
    var videodescription = $(".coveam_relatedvid_description_" + num, this).text();
    var airdate = $(".coveam_relatedvid_airdate_" + num, this).text();
    var permalink = $(".coveam_relatedvid_permalink_" + num, this).text();
    var linkedpostid = $(".coveam_relatedvid_linkedpostid_" + num, this).text();
    //add the current vid to the list of excluded videos
    var excluded = linkedpostid;
    if ($(".coveam_excluded", this).text() != " ") {
      excluded = excluded + ',' + $(".coveam_excluded", this).text();
    }
    //update the text fields to match the selected vid
    $(".coveam_videoid", this).text(videoid);
    $(".coveplayerid", this).text(coveplayerid);
    $(".youtubeid", this).text(youtubeid);
    $(".coveam_videotitle", this).text(videotitle);
    $(".coveam_videodescription", this).text(videodescription);
    $(".coveam_airdate", this).text(airdate);
    $(".coveam_video_override_encoded", this).text(video_override_encoded);
    $(".coveam_excluded", this).text(excluded);
    $(".coveam_autoplay", this).text('autoplay');

    //draw that player
    $(".coveam_player", this).height("100%");
    $(this).coveamDisplayPlayer();

    //update the title+dek+permalink+sharing buttons 
    if ("" == $(".inserted-before-video-excerpt").text() ) {
      $(".video-excerpt").before('<div class="inserted-before-video-excerpt"></div>');
    }
    $(".inserted-before-video-excerpt").html('<span class="nowplaying"><i class="fa fa-angle-double-up"></i> NOW PLAYING:</span>' + videotitle );
    $(".video-excerpt").html(videodescription);
    $(".video-package").html('');
    $(".dark-comment-jump a").attr("href", permalink + '#disqus_thread');
    $(".addthis_toolbox").attr("addthis:url", permalink);
    $(".addthis_toolbox").attr("addthis:title", videotitle);
    addthis.toolbox(".addthis_toolbox");
    // get a new set of related videos
    $.ajax({
      type: "post",
      url: wpURL+'wp-admin/admin-ajax.php',
      data:{
        'action': 'coveam_ajax_relatedvideos',
        'videoid': videoid,
        'excluded': excluded
      },
      dataType:'json',
      success: function(response){
        var num = 1;
        //update the related video info on the page
        $.each(response, function(i, obj) {
            $(".coveam_relatedvid_id_" + num).text(obj.ID);
            $(".coveam_relatedvid_linkedpostid_" + num).text(obj.ID);
            $(".coveam_relatedvid_coveplayerid_" + num).text(obj.coveplayerid);
            $(".coveam_relatedvid_youtubeid_" + num).text(obj.youtubeid);
            $(".coveam_relatedvid_title_" + num).text(obj.title);
            $(".coveam_relatedvid_description_" + num).text(obj.description);
            $(".coveam_relatedvid_airdate_" + num).text(obj.airdate);
            $(".coveam_relatedvid_permalink_" + num).text(obj.permalink);
            $(".coveam_relatedvid_img_" + num).text(obj.thumbnail_url);
            $(".coveam_player debug_" + num).text(obj.debug);
            //then we update the 'more video' links
            $(".sidebar-more-video .item-" + num + " a").attr("href", obj.permalink);   
            $(".sidebar-more-video .item-" + num + " img").attr("src", obj.thumbnail_url);
            $(".sidebar-more-video .item-" + num + " .txt").html(obj.title); 
            num++;
        });
      }
    });
  }



  //COVE-specific functions
  function SetUpCoveListener () {
    if(typeof window.addEventListener==='function'){
      window.addEventListener("message",receiveVideoPBSMessage,false);
    }else{
      window.attachEvent("onmessage",receiveVideoPBSMessage);
    }
  }

  var VideoPBSpolltimer = null;
  function receiveVideoPBSMessage(event){
    console.log(event);
    var firstdiv = $(".video-wrapper").first().attr("id");
    if (event.source === $('#' + firstdiv + ' .partnerPlayer')[0].contentWindow) {
    var data=event.data,
      action=event.data.split('::')[0],
      value=event.data.split('::')[1];
      if (action==="video" && value==="playing") {
        console.log('Polling for status');
        clearInterval(VideoPBSpolltimer);
        VideoPBSpolltimer = null;
        VideoPBSpolltimer = setInterval(function() {
          var firstdiv = $(".video-wrapper").first().attr("id");
          $('#' + firstdiv + ' .partnerPlayer')[0].contentWindow.postMessage('getState', 'http://video.pbs.org/');
        }, 1000);
      } else if (action==="getState" && value==="IDLE") {
        console.log('Video has ended');
        clearInterval(VideoPBSpolltimer);
        VideoPBSpolltimer=null;
        showPlayNextScreen();
      }
    }
  }

  //YouTube-specific functions

  var ytplayer;
  window.onYouTubeIframeAPIReady = function() {
    console.log('Youtube listener started');
    var firstdiv = $(".video-wrapper").first().attr("id");
    var ytplayerid = firstdiv + '_YouTubePlayer';
    ytplayer = new YT.Player(ytplayerid, {
      events: {
        'onStateChange': function(event) {
          if (event.data == YT.PlayerState.ENDED) {
            console.log('Video has ended');
            showPlayNextScreen();
          }
        }
      }
    });
  }

  var YouTubeAPIScript = null;
  function SetUpYouTubeAPI () {
    if (! YouTubeAPIScript) {
      YouTubeAPIScript = $.getScript("//www.youtube.com/iframe_api");
    }
  }


}( jQuery ));

