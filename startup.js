pimcore.registerNS("pimcore.plugin.computervision");

pimcore.plugin.computervision = Class.create(pimcore.plugin.admin, {
    getClassName: function() {
        return "pimcore.plugin.computervision";
    },

    initialize: function() {
        pimcore.plugin.broker.registerPlugin(this);
    },
 
    pimcoreReady: function (params,broker){
        //alert("ComputerVision Plugin Ready!");


    },

    postOpenAsset: function(asset) {
        //alert('post open asset!');

        var self = this;
        var index = 10;

        if (asset.type == 'folder') {
            index = 8;
        }


        asset.toolbar.insert(index, {
            text: 'Computer Vision',
            itemId: 'computervision',
            scale: 'medium',
            handler: function(button) {
                
                if (asset.type == 'folder') {
                    var goOn = confirm('Do you really want to analyze all images of this folder? This may take a while.');
                    if (!goOn) {
                        return;
                    }

                    pimcore.helpers.loadingShow();
                }

                Ext.Ajax.request({
                    url: "/plugin/ComputerVision/admin/get-data",
                    success: function(data) {
                        var response = JSON.parse(data.responseText);
                        
                        if (response.success == false) {
                            alert(response.message);
                            pimcore.helpers.loadingHide();

                        } else {
                            asset.reload();

                            if (asset.type == 'folder') {
                                pimcore.helpers.loadingHide();

                            } else {

                                window.setTimeout(function(){
                                    var assetPanel = pimcore.globalmanager.get("asset_" + asset.id);
                                    assetPanel.tabbar.setActiveTab(2);
                                }, 500);
                            }
                        }

                    },
                    params: {
                        id: asset.id,
                        type: asset.type
                    }
                });
            }
        });
    }
});

var computervisionPlugin = new pimcore.plugin.computervision();

