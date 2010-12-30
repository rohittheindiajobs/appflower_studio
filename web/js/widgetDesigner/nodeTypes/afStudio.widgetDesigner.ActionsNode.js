afStudio.widgetDesigner.ActionsNode = Ext.extend(afStudio.widgetDesigner.CollectionNode, {
    createContextMenu: function(){
        this.contextMenu = new Ext.menu.Menu({
            items: [
                {
                    text: 'Add action',
                    handler: this.addAction,
                    scope: this,
                    iconCls: 'icon-add'
                }
            ]
        });
    },
    addAction: function(){
        var newNode = new afStudio.widgetDesigner.ActionNode;
        this.appendChild(newNode);
        if (this.rendered) {
            this.expand();
        }
        return newNode;
    },
    contextMenuHandler: function(node, e){
        node.select();
        this.contextMenu.showAt(e.getXY());
    },
	getNodeConfig: function(){
        var config = {
            text: 'Actions',
            leaf: true,
            listeners: {
                contextmenu: this.contextMenuHandler
            },
            id: 'i:actions'
        };
        return config;
	},
    configureForValue: function(id, value){
        if (id == 'i:action') {
            if (!Ext.isArray(value)) {
                value = [value];
            }
            for(var i=0;i<value.length;i++){
                var newNode = this.addAction();
                newNode.configureFor(value[i]);
            }
        } else {
            afStudio.widgetDesigner.ActionsNode.superclass.configureForValue(this, arguments);
        }
    },
    dumpChildsData: function(){
        var data = [];
        this.eachChild(function(childNode){
            data.push(childNode.dumpDataForWidgetDefinition());
        });

        return {'i:action': data};
    }
});