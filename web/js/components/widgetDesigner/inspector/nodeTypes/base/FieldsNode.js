/**
 * Node that represents list or edit widgets fields
 * Widgets like list and edit contains many fields (i:field or i:column elements).
 * Responsibilities of this class is to manage informations from widget definition like:
 * i:grouping, i:set, i:ref - for edit type widget
 * It also keeps track of ordering of fields (list and edit widgets)
 * 
 * @class afStudio.wi.FieldsNode
 * @extends afStudio.wi.CollectionNode
 */ 
afStudio.wi.FieldsNode = Ext.extend(afStudio.wi.CollectionNode, {
    
	constructor : function(config) {
		/**
		 * Ordered array of children nodes.
		 * @property childIdsOrdered
		 * @type {Array}
		 */
	    this.childIdsOrdered = [];
	    
	    afStudio.wi.FieldsNode.superclass.constructor.apply(this, arguments);	    
	}//eo constructor    
   
    /**
     * template method
     * @override
     */
    ,createProperties : function() {
		this.addProperties([
       		new afStudio.wi.PropertyTypeString({id: 'url', label: 'Url'}).create(),
       		new afStudio.wi.PropertyTypeString({id: 'action', label: 'Action'}).create(),
       		new afStudio.wi.PropertyTypeBoolean({id: 'classic', label: 'Classic'}).create(),
       		new afStudio.wi.PropertyTypeString({id: 'bodyStyle', label: 'Body Style'}).create(),
       		new afStudio.wi.PropertyTypeString({id: 'redirect', label: 'Redirect'}).create(),       		
       		new afStudio.wi.PropertyTypeBoolean({id: 'remoteLoad', label: 'Remote Load'}).create(),
       		new afStudio.wi.PropertyTypeString({id: 'plugin', label: 'Plugin'}).create()
		]);
    }//eo createProperties	
	
    /**
     * @override
     */
    ,addChild : function() {
        var newNode = afStudio.wi.FieldsNode.superclass.addChild.apply(this, arguments);
        this.childIdsOrdered.push(newNode.id);
        
        return newNode;
    }//eo addChild
    
    /**
     * Deletes child node.
     * @param {Ext.tree.TreeNode} node
     */
    ,deleteChild : function(node) {
    	if (this.childIdsOrdered.indexOf(node.id) != -1) {
	    	this.childIdsOrdered.remove(node.id);
	    	node.destroy();    	
    	}
    }//eo deleteChild
    
    //TODO: I violated DRY principle here, BaseNode::dumpChildsData() should be refactored
    // There is also custom implementation of dumpChildsData inisde CollectioNode class
    ,dumpChildsData : function() {
        var data = [],
        	childNodes = [],
        	ret = {};
        	
        for (var i = 0; i < this.childIdsOrdered.length; i++) {
        	var n = this.findChild('id', this.childIdsOrdered[i]);
        	childNodes.push(n);
        }
        for (var i = 0; i < childNodes.length; i++) {
            data.push(childNodes[i].dumpDataForWidgetDefinition());
        }
        if (data.length == 0 && !this.dumpEvenWhenEmpty) {
            return ret;
        }
        ret[this.childNodeId] = data;
        
        return ret;
    }//eo dumpChildsData
    
    ,setChildsOrder : function(childIdsOrdered) {
        this.childIdsOrdered = childIdsOrdered;
    }
});

// Use code below to try out ordering of fields
// first load up some widget into WI
// then use code below to find out of node ids
// finally call setChildsOrder() to set new order of fields
// after saving widget - physical order of fields in XML should be just like given to setChildsOrder()

/**
var wd = afStudio.getWidgetsTreePanel().widgetDefinition;
var fieldsNode = wd.rootNode.getFieldsNode();
fieldsNode.eachChild(function(node){
    console.log("'"+node.id+"',");
});

fieldsNode.setChildsOrder([
'xnode-186',
'xnode-189',
'xnode-195',
'xnode-198',
'xnode-184',
'xnode-181',
'xnode-192',
'xnode-181',
]);

 *
 *  */