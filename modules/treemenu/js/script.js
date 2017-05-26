window.onload = function(){
   tmspans=document.getElementsByName("tmspan");
	if(tmspans.length==0)
	   tmspans=getElementsByName_iefix("span", "tmspan");
    var i=0;
    while(i<tmspans.length)
    {
       tmspans[i].onmouseover=function(){__tmHighlight(this);};
       tmspans[i].onmouseout=function(){__tmLowlight(this);};
       i++;
    }
}

function getElementsByName_iefix(tag, name)
{
   var elem = document.getElementsByTagName(tag);
   var arr = new Array();
   for(i = 0,iarr = 0; i < elem.length; i++) {
      att = elem[i].getAttribute("name");
      if(att == name) {
         arr[iarr] = elem[i];
         iarr++;
      }
   }
   return arr;
}
function __tmHighlight(span) {
    if (span.getElementsByTagName("span")[0].className != "tmRegular")
        return;
    span.getElementsByTagName("span")[0].className = "tmHighlighted";
}
function __tmLowlight(span) {
    if (span.getElementsByTagName("span")[0].className != "tmHighlighted")
        return;
    span.getElementsByTagName("span")[0].className = "tmRegular";
}
function str_replace(search, replace, subject) {
    return subject.split(search).join(replace);
} 

 function __tmPostBack(nodeid,parentid)
 {
     var parentid = nodeid.substr(0, nodeid.indexOf("_"));
   // var id    = (node.parentNode.id != null) ? node.parentNode.id : '';
    if(jTMenu("node"+nodeid)!=null)
       jTMenu("node"+nodeid).value="e";
    jTMenu('nodeid' + parentid).value = nodeid;
    jTMenu('frmnodes' + parentid).submit();
}
function __tmSwitch(nodeid, directory) {
    
    var parentid = nodeid.substr(0, nodeid.indexOf("_"));
    icon = jTMenu("pic" + nodeid);
    classNaming = jTMenu(nodeid).className;
    if (classNaming == "tmExpanded") {
        jTMenu("img" + nodeid).src = str_replace("minus", "plus", jTMenu("img" + nodeid).src);
        jTMenu(nodeid).className = "tmCollapsed";
        if (icon != null)
            icon.src = str_replace("folderopened", "folder", icon.src);
        jTMenu("node" + nodeid).value = "c";
    }
    else if (classNaming == "tmCollapsed") {
        if (directory != "") {
            $.get(jTMenu("path").value + "inc/getfiles.php",
                { directory: directory, style: jTMenu("style" + parentid).value,
                    folderIcons: jTMenu("folderIcons" + parentid).value, fileIcons: jTMenu("fileIcons" + parentid).value, parentId: nodeid, path: jTMenu("path").value
                },
                function (data) {
                    
                    //jTMenu("innercontainer" + parentid).innerHTML = data;
                    numNodes = data.substring(0, data.indexOf("<") - 1);
                    if (numNodes == 0) {
                    jTMenu("img" + nodeid).style.visibility = "hidden";
                    }
                    else {
                    data = data.substring(data.indexOf("<"));
                    if (jTMenu("showNumFiles" + parentid).value == 1) {
                    jTMenu("text" + nodeid).innerHTML += " (" + numNodes + ")";
                    }
                    jTMenu(nodeid).innerHTML += data;
                    jTMenu("span" + nodeid).onmouseover = function () {
                    __tmHighlight(this);
                    };
                    jTMenu("span" + nodeid).onmouseout = function () {
                    __tmLowlight(this);
                    };
                    jTMenu("img" + nodeid).onclick = function () {
                    __tmSwitch(nodeid, "");
                    };
                    }
                    jTMenu("span" + nodeid).onclick = function () {
                    __tmPostBackAjax(nodeid, "/", "file");
                    };

                });
        }
        jTMenu("img" + nodeid).src = str_replace("plus", "minus", jTMenu("img" + nodeid).src);
        jTMenu(nodeid).className = "tmExpanded";
        if (icon != null)
            icon.src = str_replace("folder", "folderopened", icon.src);
        jTMenu("node" + nodeid).value = "e";
    }

}
function jTMenu(id)
{
   return document.getElementById(id);
}
function __tmPostBackAjax(newid, filename, mode) {
    
    var parentid=newid.substr(0,newid.indexOf("_"));
    if (mode == "file")
        jTMenu("innercontainer" + parentid).innerHTML = "<img src='" + jTMenu("path").value + "styles/ajax_loading.gif' />";
    oldid = jTMenu("nodeid" + parentid).value;
    if (oldid.length != 0) {
        jTMenu("text" + oldid).className = "tmRegular";
    }

    
    jTMenu("nodeid" + parentid).value = newid;
    if (oldid != "" && jTMenu(oldid).className == "tmExpanded" && newid.substring(0, newid.lastIndexOf("_")) == oldid.substring(0, oldid.lastIndexOf("_")))
        __tmSwitch(oldid);
    

    if (jTMenu(newid).className == "tmCollapsed") {
        if (mode == "filesystem") {
            __tmSwitch(newid, filename);
        }
        else __tmSwitch(newid, "");
    }

    jTMenu("text" + newid).className = "tmSelected";

    if (oldid != null && jTMenu("code" + oldid) != null) {
        jTMenu("code" + oldid).style.display = 'none';
    }
    
    if (mode == "file") {
        $.get(jTMenu("path").value + "inc/getcontent.php", { filename: filename },
           function (data) {
               jTMenu("innercontainer" + parentid).innerHTML = data;
           });
    }
    else if (mode == "code") {
        jTMenu("code" + newid).style.display = '';
        jTMenu("innercontainer" + parentid).innerHTML = '';
    }
}