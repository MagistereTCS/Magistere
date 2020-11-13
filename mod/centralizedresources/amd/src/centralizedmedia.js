define(['media_videojs/video-lazy', 'local_magisterelib/videojs-resolution-switcher', 'local_magisterelib/videojs-markers'], function(videojs){
    var _src = [];

    function clearSrc()
    {
        _src = [];
    }

    function addSrc(src, type, label, res)
    {
        _src.push({
            src: src,
            type: type,
            label: label,
            res: res
        });
    }

    function updateSrc(id)
    {
        var player;
        // if this a link to a cr video there is no player
        try {
            player = videojs(id, {
                plugins: {
                    videoJsResolutionSwitcher: {
                        default: 'high',
                        dynamicLabel: 1
                    }
                }
            });
        } catch(e) {}

        if (player) {
            player.markers({
                markers: [],
                markerTip: {
                    display: true,
                    text: function(marker){
                        return marker.text;
                    }
                }
            });
    
    
            if(_src.length > 1){
                player.updateSrc(_src);
            }else{
                player.src(_src[0]);
            }
    
            player.on('loadedmetadata', function(){
                var tracks = player.textTracks();
                var chapterTrack = null;
    
                for (var i=0; tracks.length > i; i++) {
                    if ('chapters' === tracks[i].kind) {
                        chapterTrack = tracks[i];
                        break;
                    }
                }
    
                if(!chapterTrack){
                    return;
                }
    
                chapterTrack.addEventListener('cuechange', function() {
                    player.markers.removeAll();
    
                    var markers = [];
                    for(var i = 0; i < chapterTrack.cues.length; i++){
                        var pos = chapterTrack.cues[i].startTime;
                        var text = chapterTrack.cues[i].text;
    
                        markers.push({
                            time: pos,
                            text: text
                        });
    
                    }
    
                    player.markers.add(markers);
                });
            });
        }
    }

    return {
        clearSrc: clearSrc,
        addSrc: addSrc,
        updateSrc: updateSrc
    };
});