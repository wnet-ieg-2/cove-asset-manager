//version 1.2.1 2015-01-20 
//minor tweak to youtube player vars
//version 1.2 2014-10-30
//pointing ajax call to particular URL
//version 1.1.3 2014-4-22
//refined clickhandling to stop playnext in certain contexts. Also found case where COVE video wasn't getting proper resizing
//version 1.1.2 2014-4-1
//redoing youtube handling to draw player via native YT methods rather than applying event listener to iframe, which youtube kept breaking
//version 1.1.1 2014-3-21
//fails over to youtube if cove/jwplayer doesn't initialize within 5 seconds
//version 1.1 2014-3-4
//fires script automatically on any div classed 'coveam-videoplayer'
(function ($) {
	$.fn.coveamDisplayPlayer = function() {
    var $divid = $(this).attr("id");
    var $showchrome = $(".coveam_playerchrome", this).text();
    var $coveplayer_chrome = "&topbar=true";
    var $autoplay = $(".coveam_autoplay", this).text();
    var $youtubevars = {};
    $youtubevars["rel"]=0;
    $youtubevars["showinfo"]=0;
    $youtubevars["autohide"]=1;
    $youtubevars["modestbranding"]=1;
    if ($showchrome == "hide") {
      $coveplayer_chrome = "&topbar=false";
    }
    var $coveplayer_autoplay = '&autoplay=false';
    if ($autoplay == "autoplay") {
      $coveplayer_autoplay = '&autoplay=true';
      $youtubevars["autoplay"]=1;
    }
	  var $covepartnerplayer_template = "<div class='video-wrap' style='width:100%; padding-top: 62.5%; position:relative;'><iframe class='partnerPlayer' frameborder='0' marginwidth='0' marginheight='0' scrolling='no' width='100%' height='100%' style='position:absolute; top:0;' src='//player.pbs.org/widget/partnerplayer/COVEPLAYERID/?start=0&end=0&chapterbar=false&endscreen=false" + $coveplayer_chrome + $coveplayer_autoplay +"' allowfullscreen></iframe></div>";
	  var $youtubeplayer_template = '<div id="' + $divid + '_YouTubePlayer"></div>';
    var $videoid = $(".coveam_videoid", this).text();
	  var $coveplayerid = $(".coveplayerid", this).text();
	  var $youtubeid = $(".youtubeid", this).text();
	  var $covepreferred = $(".coveam_covepreferred", this).text();
	  var $videotitle = $(".coveam_videotitle", this).text();
	  var $videodescription = $(".coveam_videodescription", this).text();
	  var $airdate = $(".coveam_airdate", this).text();
	  var $this_coveplayer = $covepartnerplayer_template.replace('COVEPLAYERID', $coveplayerid);
	  var $this_youtubeplayer;
    if ($youtubeid && ($youtubeid !== null)) {
      $this_youtubeplayer = $youtubeplayer_template.replace('YOUTUBEID', $youtubeid);
    }
    var $this_alternateplayer = decodeURIComponent($(".coveam_video_override_encoded", this).text());
	  var $thisplayer;
    var $playertype = 'cove';
    if ($coveplayerid) {
      $thisplayer = $this_coveplayer;
      if (($covepreferred == "false") && $this_youtubeplayer) {
        $thisplayer = $this_youtubeplayer;
        $playertype = 'youtube';
      }
	  } else if ($this_youtubeplayer) {
      $thisplayer = $this_youtubeplayer;
      $playertype = 'youtube';
    } else if ($this_alternateplayer) {
      $thisplayer = $this_alternateplayer;
      $playertype = null;
    } else {
      $thisplayer = '<div class="errormessage">This video is not currently available.</div>';
      $playertype = null;
    }
	  $(".coveam_player", this).delay(100).html($thisplayer);
    //cove listener can only be started AFTER the players are instantiated
    if ($playertype == 'cove') {
      SetUpCoveListener();
      //check to see if the COVE listener is getting messages yet. 
      //if not, wait 5 seconds then switch to YouTube or alternate
      var fallbackplayer = ($this_youtubeplayer) ? $this_youtubeplayer : $this_alternateplayer;
      if( ! $("html").hasClass("ie8") && fallbackplayer ) {
        var that = this;
        if (! VideoPBSMessagesRecieved) {
          setTimeout(function() {
            if(! VideoPBSMessagesRecieved) {
              $(".coveam_player", that).html(fallbackplayer);
              if ($this_youtubeplayer) {
                $(".coveam_covepreferred").text("false");
                console.log('falling back to YouTube');
                WriteOutYoutubePlayer($youtubeid, $divid, $youtubevars);
              } else {
                console.log('falling back to alternative player');
                $(".coveam_player iframe", that).wrap("<div class='video-wrap' style='width:100%; padding-top: 62.5%; position:relative;'></div>");
                $(".coveam_player iframe", that).css({"position" : "absolute", "top":"0", "width":"100%", "height":"100%"});
              }
            }
          }, 5000);
        }
      }
    } else if ($playertype == 'youtube') {
      WriteOutYoutubePlayer($youtubeid, $divid, $youtubevars);
    }
    //last step; insert brief delay and then enable playnext if it was disabled
    if (! playnextenabled) {
      setTimeout(function() {
        playnextenabled = true;
      }, 5000);
    }
 
	  return this;
	};


  //clicking on ajax bubbles disables playnext 
  //note that video initiation function above re-enables playnext
  var playnextenabled = true;
  $(document).on('click', '.changevideo', function(e){
    playnextenabled = false;
  });


 
  var playnexttimeout;
  //playnext screen
  function showPlayNextScreen() {
    if (playnextenabled) {
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
        morechoices = morechoices + '<div class="leftchoice choice" style="float:left; width: 50%;"><h4>NEXT UP: in <span class="countdown">10</span> seconds <div id="coveam_pauser">PAUSE <i class="fa fa-pause"></i></div></h4><div class="choiceimage"><a href="' + videopermalink_1 + '"><img src="' + videothumb_1 + '" /></div><p><a href="' + videopermalink_1 + '">' + videotitle_1 + '</a></p></div>';
        morechoices = morechoices + '<div class="rightchoice choice" style="float:right; width: 50%;"><h4>You May Also Like:</h4><div class="choiceimage"><a href="' + videopermalink_2 + '"><img src="' + videothumb_2 + '" /></div><p><a href="' + videopermalink_2 + '">' + videotitle_2 + '</a></p></div></div>';
        if (videoid_1) {
          $(firstdiv + " .coveam_player").html(morechoices);
          $(firstdiv).css("min-height", thisheight);
          $('#coveam_pauser', firstdiv).css("cursor","pointer");
          $(firstdiv).on('click', '#coveam_pauser', function(e) {
            e.preventDefault();
            console.log('paused');
            clearTimeout(playnexttimeout);
          });
          CountDownToNextVideo(firstdiv, 11);
        }
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
    $(this).coveamDisplayPlayer();

    //find the parent on general principle
    var thisparent = $(this).parent();
    
    //format the date more nicely using jQueryUI Datepicker
    var formatteddate = airdate;
    if ($.datepicker) {
      formatteddate = $.datepicker.parseDate('yy-mm-dd', airdate);
      formatteddate = $.datepicker.formatDate('MM d, yy', formatteddate);
    }

    //update the title+dek+permalink+sharing buttons 
    if ("" == $(".inserted-before-video-excerpt", thisparent).text() ) {
      $(".video-excerpt", thisparent).before('<div class="inserted-before-video-excerpt"></div>');
    }
    $(".inserted-before-video-excerpt", thisparent).html('<span class="nowplaying"><i class="fa fa-angle-double-up"></i> NOW PLAYING:</span>' + videotitle );
    $(".video-excerpt", thisparent).html(videodescription);
    $(".video-title", thisparent).html('<a href="' + permalink + '">' + videotitle + '</a>');
    $(".video-options a", thisparent).attr("href", permalink);
    $(".video-options a.disqus-link", thisparent).attr("href", permalink + '#disqus_thread');
    $(".video-options a.transcript-link", thisparent).attr("href", permalink + '#transcript');
    $(".video-date", thisparent).text(formatteddate);
    $(".video-package", thisparent).html('');
    $(".dark-comment-jump a", thisparent).attr("href", permalink + '#disqus_thread');
    $(".addthis_toolbox", thisparent).attr("addthis:url", permalink);
    $(".addthis_toolbox", thisparent).attr("addthis:title", videotitle);
    addthis.toolbox(".addthis_toolbox", thisparent);
    // get a new set of related videos
    $.ajax({
      type: "GET",
      url: wpURL+ "ajax/ajax-related-videos/?" + 'videoid=' + videoid + '&excluded=' + excluded,
      dataType:'html',
      
      success: function(response){
        var returnstring = $(response).filter('#json').text();
	var returndata = $.parseJSON(returnstring);
        var num = 1;
        //update the related video info on the page
        $.each(returndata, function(i, obj) {
            $(".coveam_relatedvid_id_" + num).text(obj.ID);
            $(".coveam_relatedvid_linkedpostid_" + num).text(obj.ID);
            $(".coveam_relatedvid_coveplayerid_" + num).text(obj.coveplayerid);
            $(".coveam_relatedvid_youtubeid_" + num).text(obj.youtubeid);
            $(".coveam_relatedvid_title_" + num).text(obj.title);
            $(".coveam_relatedvid_description_" + num).text(obj.description);
            $(".coveam_relatedvid_airdate_" + num).text(obj.airdate);
            $(".coveam_relatedvid_permalink_" + num).text(obj.permalink);
            $(".coveam_relatedvid_img_" + num).text(obj.thumbnail_url);
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
  var VideoPBSMessagesRecieved = false;

  var VideoPBSListenerStarted = false;
  function SetUpCoveListener () {
    if (VideoPBSListenerStarted) {
      return;
    }
    if(typeof window.addEventListener==='function'){
      window.addEventListener("message",receiveVideoPBSMessage,false);
    }else{
      window.attachEvent("onmessage",receiveVideoPBSMessage);
    }
    VideoPBSListenerStarted = true;
  }

  var VideoPBSpolltimer = null;
  function receiveVideoPBSMessage(event){
    if ( (event.origin == 'http://player.pbs.org') || (event.origin == 'https://player.pbs.org') ) {
      var origin_uri = event.origin;
      if (! VideoPBSMessagesRecieved) {
        console.log("PBS Video messaging started");
      }
      VideoPBSMessagesRecieved = true;
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
            $('#' + firstdiv + ' .partnerPlayer')[0].contentWindow.postMessage('getState', origin_uri);
          }, 1000);
        } else if (action==="video" && value==="finished") {
          console.log('Video has ended');
          clearInterval(VideoPBSpolltimer);
          VideoPBSpolltimer=null;
          showPlayNextScreen();
        }
      }
    }
  }

  //YouTube-specific functions

  function WriteOutYoutubePlayer(youtubeid, divid, playervars){
    if (! YouTubeAPILoaded) {
      console.log('YouTube api not loaded');
      setTimeout(function(){WriteOutYoutubePlayer(youtubeid, divid, playervars);},100);
    }else{
    var ytplayerid = divid + '_YouTubePlayer';
    var jsonplayervars = JSON.stringify(playervars);
    var ytplayer = new YT.Player(ytplayerid, {
      height: '100%',
      width: '100%',
      videoId: youtubeid,
      playerVars: playervars,
      events: {
        'onReady': function(event) {
          console.log('Video is ready');
        },
        'onStateChange': function(event) {
          if (event.data == YT.PlayerState.ENDED) {
            console.log('Video has ended');
            var firstdiv = $(".video-wrapper").first().attr("id") + '_YouTubePlayer';
            if (firstdiv == ytplayerid) { 
              showPlayNextScreen();
            }
          }
        }
      }
    });
    $("#" + ytplayerid).wrap("<div class='video-wrap' style='width:100%; padding-bottom: 56.25%; position:relative;'></div>");
    $("#" + ytplayerid).css({"position" : "absolute", "top":"0"});
    }
  }

  
  var YouTubeAPIScript = null;
  function SetUpYouTubeAPI () {
    if (! YouTubeAPIScript) {
      YouTubeAPIScript = $.getScript("//www.youtube.com/iframe_api");
      console.log('youtube api script in place');
    }
  }
  //autostart the youtube API
  SetUpYouTubeAPI ();

  var YouTubeAPILoaded = false;
  window.onYouTubeIframeAPIReady = function() {
    YouTubeAPILoaded = true;
    console.log('youtube api loaded');
  };

 // automatically fires the script on any appropriately named div
  $(".coveam-videoplayer").each(function() {
    $(this).coveamDisplayPlayer();
  });


}( jQuery ));

