M.block_progress = {
    wwwroot: '',
    preLoadArray: new Array(),
    tickIcon: new Image(),
    crossIcon: new Image(),
    displayDate: 1,

    init: function (YUIObject, root, modulesInUse, date) {
        var i;

        // Remember the web root
        this.wwwroot = root;

        // Rember whether the now indicator is displayed (also hides date)
        this.displayDate = date;

        // Preload icons for modules in use
        for(i=0; i<modulesInUse.length; i++) {
            this.preLoadArray[i] = new Image();
            this.preLoadArray[i].src = M.util.image_url('general/icon_progress_activity_'+modulesInUse[i],'theme');
        }
        this.tickIcon.src = M.util.image_url('tick', 'block_progress');
        this.crossIcon.src = M.util.image_url('cross', 'block_progress');
    },

    showInfo: function (mod, type, id, name, message, dateTime, instanceID, userID, icon) {

        // Dynamically update the content of the information window below the progress bar
        var content  = '<div class="progressEventInfo_activity"><div style="float:left;"><a href="'+this.wwwroot+'/mod/'+mod+'/view.php?id='+id+'">';
            content += '<img src="'+M.util.image_url('general/icon_progress_activity_'+mod,'theme')+'" alt="Module icon" class="moduleIcon" /></a></div>';
            content += '<div style="float:left; width:75%; word-wrap: break-word"><span class="progressEventInfo_activity_title">'+type+'</span><br/><a href="'+this.wwwroot+'/mod/'+mod+'/view.php?id='+id+'">'+name+'</a><br /><div class="status">'+message+'</div> ';
            if (this.displayDate) {
                content += '<div style="clear:both; color: #b9b9b9;">'+M.str.block_progress.time_expected+': '+dateTime+'</div></div>';
            } else {
                content += '&nbsp;</div>';
            }
            content += '</div>';
        document.getElementById('progressBarInfo'+instanceID+'user'+userID).innerHTML = content;
    }
};