YUI.add('moodle-theme_academy_clean-resourceoverlay', function (Y, NAME) {

var RESOURCEOVERLAYNAME = 'Academy theme resource overlay',
    ACTIVITYSELECTOR = '.activity.resource .activityinstance a',
    IFRAMECLASS = 'overlay',
    RESOURCEOVERLAY;

RESOURCEOVERLAY = function() {
    RESOURCEOVERLAY.superclass.constructor.apply(this, arguments);
};

Y.extend(RESOURCEOVERLAY, Y.Base, {
    
    overlay : null,
    initializer : function() {
        var self = this;
        
        var resourcenodes = Y.all(ACTIVITYSELECTOR).each(processNodes);

        Y.delegate('mousedown', function(e){
            // Stop the event's default behavior
            e.preventDefault();

            // Stop the event from bubbling up the DOM tree
            e.stopPropagation();
            
            var params = e.target.getAttribute('onclick');
            
            // Get the resource attributes from the onclickurl
            var width = getValueFromOnClick(params, 'width');
            var height = getValueFromOnClick(params, 'height');

            var location = this.getAttribute('href')+'&redirect=1';

            //display an overlay
            var title = '',
                content = Y.Node.create('<iframe class="'+IFRAMECLASS+'" width="'+width+'" height="'+height+'" src="'+location+'"></iframe>'),
                d = new M.core.dialogue({
                    headerContent :  title,
                    bodyContent : content,
                    lightbox : true,
                    width : width,
                    height : 'auto',
                    centered : true,
                    modal: true,
                    draggable : false,
                    zindex : 5, // Display in front of other items
                    shim : false,
                    closeButtonTitle : this.get('closeButtonTitle'),
                    hideOn: [
                        {
                            eventName: 'clickoutside'
                        }
                    ]
                });

            // Videos play in hidden iframe so destroy the node on close
            d.get('buttons').header[0].on('click', function(e){
                var destroyAllNodes = true;
                d.destroy(destroyAllNodes);
            });

            self.dialog = d;
            d.render(Y.one(document.body));

        }, Y.one(document.body), filterActivities);

    }

}, {
    NAME : RESOURCEOVERLAYNAME,
    ATTRS : {
        name : {
            validator : Y.Lang.isString,
            value : 'resourceoverlay'
        }
    }
});

function processNodes(node) {
    // If the node has an onClick attribute, rename it to avoid it being run
    if (node.getAttribute('onclick').length > 2) {
        /* TESTING. */
        node.append("&nbspPOPUP");            
    }
}

function filterActivities(node) {
    // Limit overlay to activities that open in a pop-up window
    if (node.getAttribute('onclick').length > 2 && node.test(ACTIVITYSELECTOR)) {
        return true;
    }
  
    return false;
}

function getValueFromOnClick(onClick, value) {
    var regex, results;
    
    // Get the value from the onclickurl
    regex = new RegExp(value + '=([0-9]+)', 'i');
    results = onClick.match(regex);
    
    if (results === null) {
        return null;
    }
    return results[1];
}

M.theme_academy_clean = M.theme_academy_clean || {};
M.theme_academy_clean.resourceoverlay = {
    init: function(config) {
        return new RESOURCEOVERLAY(config);
    }
};


}, '@VERSION@', {"requires": ["base", "node", "event-delegate", "moodle-core-dialogue", "moodle-core-notification"]});
