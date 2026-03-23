<?php
namespace app\controller\manager;
use \lib\StdLib as lib;
class MenuManager  extends \fw\controller\manager\StdManager
{   
    // define the page numbering for throughout the app
    private $trace = false;
    private $pages;
    const LOGINPAGE = 1;
    const STARTNEWPWPAGE = 11;
    const ENTERCODEPAGE = 12;
    const ENTERNEWPWPAGE = 13;
    const ROSTER1 = 101;
    const ROSTER2 = 102;
    const ROSTER3 = 103;
    const ROSTER4 = 104;
    const ROSTER5 = 105;
    const ROSTER6 = 106;
    const ROSTER7 = 107;
    const ROSTER8 = 108;
    const ROSTER9 = 109;
    const ROSTER10 = 110;
    const PROFILEPAGE       = 200;
    const TASKPAGE          = 310;
    const ROLEPAGE          = 320;
    const USERPAGE          = 330;
    const CLIENTADMINPAGE   = 331;
    const ATTENDANCEADMINPAGE= 332;
    const CLIENTREPORTPAGE  = 333;
    const CLIENTVOLSPAGE    = 334;
    const ATTENDANCEVOLSPAGE = 335;
    const SESSIONPAGE       = 340;
    const PAGEPAGE          = 350;
    const ACTIONPAGE        = 360;
    const REPORTPAGE        = 370;
    const CONFIGPAGE        = 500;
    const MENUITEMPAGE      = 501;
    const LOGOUTPAGE        = 999;
    // Stock management pages
    const STOCKCATEGORYPAGE    = 401;
    const STOCKPAGE            = 402;
    const STOCKTAKEPAGE        = 403;
    const DELIVERYPAGE         = 404;
    const STOCKOUTPAGE         = 405;
    const STOCKLEVELREPORTPAGE = 406;
    private $pagenumbers = ["0"=>"Submenu Heading"];
    private $c2v;
    protected $name = "Menuitem";
    protected $linkedobject = "";
    protected $db;
    protected $menuitems = [];
    public function __construct(protected \apptable\MenuitemTable $table,
                                protected \apptable\PageTable $pagetable){
        if ($this->trace ) { echo "Enter ".__METHOD__."<br>"; }
        if ($this->trace ) {echo "Leave ".__METHOD__."<br>";}
     }
    public function init($session,$trace=false){
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        parent::init($session);
        // if (!is_null($this->db)) {
        $this->table->init($this->db);
        $this->pagetable->init($this->db);
        // }
        // $success = $this->pagetable->selectall($records,$numrows,"name",false);
        // // $records = lib::array_orderby($records,"pagenumber",SORT_ASC);
        // foreach ($records as $key => $page) {
        //     $this->pages[$page["pagenumber"]] = ["usepagenum"   =>$page["usepagenum"],
        //                                          "id"           =>$page["id"],
        //                                          "submenu"      =>$page["submenu"],
        //                                          "menuid"       =>$page["menuid"],
        //                                          "menutext"     =>$page["menutext"],
        //                                          "unrestricted" =>$page["unrestricted"],
        //                                          "pagetype"     =>$page["pagetype"],
        //                                      ];
        //     if ($page["pagetype"] != 1) {
        //         $this->pagenumbers[$page["pagenumber"]] = " - ".$page["name"];
        //     }
        // }
        if ($this->trace ) {echo "Leave ".__METHOD__."<br>";}
        // $this->test();
        // return $success;
     } 
    public function makenames($trace=false) { 
        if ($this->trace  || $trace) { echo "Enter ".__METHOD__."<br>"; }
        foreach ($this->alldata as $record) {
            $this->names[$record["id"]] = $record["menucode"]." ".$record["text"];
        }
        if ($this->trace || $trace) { echo "Leave ".__METHOD__." ".$success." <br>\n"; }
     }
    public function getpagenumberarray(){
        return $this->pagenumbers;
     }
    //=====================================================================================
    public function buildmenu($curpage,$rights,$isadmin=0,$menunumber=0) {
        return $this->buildmenuandscript ($curpage,$rights,$isadmin,$menunumber);
     } 
    public function islogout ($page_num = 0) {
        return $page_num === self::LOGOUTPAGE;
     }
    public function buildmenuandscript ($curpage,$rights,$isadmin,$menunumber,$trace=false) {
        if ($this->trace || $trace ) { echo "Enter ".__METHOD__."<br>";lib::v($curpage,$rights,$isadmin,$menunumber); }
        $script = "";
        // $this->table->selectall($menuitems,$numrows,"menucode",false);
        $this->table->selectononefield("menu_number",$menunumber,$menuitems,$numrows,false,false,"menucode");
        $menucodes = $menu = $holdmenu = $holdmenuitems =  [];
        $arrow = "<span class='submenuarrowspan'>&lt;</span>";
        $noarrow = "<span class='submenuarrowspan'>&nbsp;</span>";
// lib::pr($rights,$isadmin);
        foreach ($menuitems as $key => $item) {
            if (!$item["inactive"]) {
                $page_number = $item["page_number"];
                if ($page_number == '0' 
                    || ($isadmin && ($item["inactive"]==0)) 
                    || in_array($page_number."||VIEW",$rights) 
                    || $item["is_public"]) {
                    $holdmenuitems[$key] = $item;
                    $menucodes[$key] = $item["menucode"];
                } else {
// lib::pr($item);                
                }
            }
        }
        $menuitems = $holdmenuitems;
        // remove any menu items that point to a submenu that contains no items
        reset($menucodes);
        while (current($menucodes) !== false) {
            $key = key($menucodes);
            if ($menuitems[$key]["page_number"] == 0 )  {
                $menucode = current($menucodes);   
                $nextmenucode = next($menucodes);
                $ischild = false;
                if ($nextmenucode !== false && 
                        substr($nextmenucode,0,strlen($menucode)) === $menucode && 
                        substr($nextmenucode,strlen($menucode),1)==="_") {
                    // e.g. menucode = "02", nextmenucode="02_01"
                    $remainder = substr($nextmenucode,strlen($menucode)+1);
                    $ischild = (strpos($remainder,"_") === false); // nextmenucode is not a submenu itself
                }
                if (!$ischild) {
                    unset($menuitems[$key]);
                    unset($menucodes[$key]);
                    reset($menucodes);
                }
            } else {
                next($menucodes);
            }
        }
       // build HTML for each menu item
        $holdmenu = [];
        $this->menuitems = $menuitems;
        foreach ($menuitems as $key => $item) {
            $pagenum = $item["page_number"];
            // first determine which menu contains this item
            $pn = $item["menucode"];   //  e.g.  1_3_2
            $ppos=strrpos($pn,"_"); // note - using strrpos(), not strpos(), so ppos = 3
            $containermenucode = ($ppos===false)?"0":substr($pn,0,$ppos); //so item's menucode of 1.3.2 gives containing menu's id of 1.3
            $menu[$containermenucode] = $menu[$containermenucode]??""; // in case this is the first item in this menu 
            // now determine if this item points to a page, or to a submnenu
            $li = "<li  id='page{$pn}'";
            if ($item["page_number"] == "-1") { // i.e. a horizontal line
                $li .= " class='menuseparator'></li>";
            } else if ($item["page_number"] !=="0") { // i.e. points to page
                $li .= " class='newpage'  data-pagenumber='{$pagenum}'>{$noarrow}{$item['text']}</li>";
            } else {
                $submenucode = $item["menucode"];
                $li .= " class='submenuli'>{$arrow}{$item['text']} @@MENU{$submenucode}@@</li>"; // incude placeholder for the nested submenu
            }
            $menu[$containermenucode] .= $li; 
        }
        // now wrap the <li>s in a <ul>
        foreach ($menu as $containermenucode => $html) {
            $level = $containermenucode==0?"toplevel":"submenu";
            $html = "<ul class='menu {$level}'>{$html}</ul>";
            $menu[$containermenucode] = $html;
        }

        // now replace the submenu placeholders in $menu["0"] with the now complete submenus
        $holdmenu = $menu;
        foreach ($holdmenu as $menucode => $html) {
            if ($menucode!=0) {
                $ppos=strrpos($menucode,"_"); // note - using strrpos(), not strpos() to deal with ids like 2.4.3 (not used in this implementation)
                $parentmenucode = ($ppos===false)?"0":substr($pn,0,$ppos); //so item's menucode of 1.3 gives containing menu's id of 1
                $replaced = str_replace("@@MENU{$menucode}@@",$html,$menu[$parentmenucode]);
                $menu[$parentmenucode] = $replaced;
            }
        }
        // now build the HTML
// lib::pr($menu);
        $menuout  = <<<HTML
                        <div id='menubutton' class='clickable menu nouppercase'>
                            Menu
                            {$menu["0"]} 
                            <form id="menuactionform" method="POST" >
                                <input type="hidden" name="menuactionform" value="1"/>
                                <input type="hidden" name="pp" value="" />
                                <input type="hidden" name="p" />
                                <input type="hidden" name="menuid" value=""/>
                            </form>
                        </div>
                    HTML;
        $script = $this->buildscript();
        return $menuout.$script;
     }
    private function buildscript(){
        $script = <<<JS
                    <script>
                        jQuery(function () {
                            // jQuery(document).off('keydown').on('keydown', function(event) {
                            //     processmenushortcutkeystroke(event); 
                            // });
                            jQuery('#menubutton li.newpage').on('click', function () {
                                menuaction(jQuery(this),'');
                            });
                        })
                        function menuaction(menubutton,menuid="") {
                            menubutton.addClass('clicked');
                            document.body.style.cursor = 'wait'; 
                            // menubuttonid = menubutton.prop('id');
                            // const targetpagenum =menubuttonid.substring(0,5)=="menu_"?menubuttonid.substring(5):menubuttonid;
                            const targetpagenum =menubutton.data("pagenumber");
                            jQuery("#menuactionform input[name='p']").val(targetpagenum);
                            jQuery("#menuactionform input[name='menuid']").val(menuid);
                            jQuery("#menuactionform").trigger( "submit" );
                        } 
                        function menukeyboardhover (button){
                            $("ul.meu li.hover").removeClass("hover");
                            $(button).addClass("hover");
                            $(button+" > ul > li:first-of-type").addClass("hover");

                        }
                        function processmenushortcutkeystroke(event) {
                            if ($("#menubutton.hover,ul.menu li.hover").length) { // MENU:HOVER
                                const arrowSet = new Set();
                                arrowSet.add(37);arrowSet.add(38);arrowSet.add(39);arrowSet.add(40);
                                if (arrowSet.has(event.which)) {
                                    processmenuarrowkey(event);
                                } else if (event.key == "Escape") {
                                    if ($("ul.menu li.hover").length) {
                                        $("#menubutton.hover,ul.menu li.hover").removeClass("hover");
                                        event.stopPropagation();
                                    }
                                } else if (event.key == "Enter") {
                                    if ($("ul.menu li.hover.newpage").length) {
                                        const pagenum = $("ul.menu li.hover.newpage").data("pagenumber");
                                        jQuery("#menuactionform input[name='p']").val(pagenum);
                                        jQuery("#menuactionform input[name='menuid']").val("");
                                        jQuery("#menuactionform").trigger( "submit" );
                                        event.stopPropagation();
                                    }
                                }
                            } 
                        }
                        function processmenuarrowkey(event){
                            if ($("ul.menu li.hover").length) {
                                const arrow = event.which;
                                let newli;
                                const curli = $("ul.menu li.hover").last();
                                switch (arrow) {
                                    case 37: // left
                                            if (curli.hasClass("submenuli")) {
                                                newli = curli.find("ul li:first-child");
                                            }
                                            break;
                                    case 38: // up
                                            if (curli.prevAll().length) {
                                                newli = curli.prevAll().first(); // prevAll() siblings are in reverse order
                                                curli.removeClass("hover"); 
                                            }
                                            break;
                                    case 39: // right
                                            const curid = curli.prop("id");
                                            const curcode = curid.substring(4);
                                            const lastdotIndex = curcode.lastIndexOf("_");
                                            if (lastdotIndex !== -1) {
                                                const newcode = curcode.substring(0,lastdotIndex);
                                                newli = jQuery('li').has("ul.menu li.hover");
                                                curli.removeClass("hover"); 
                                            }
                                            break;
                                    case 40: // down
                                            if (curli.nextAll().length) {
                                                newli = curli.nextAll().first();
                                                curli.removeClass("hover"); 
                                            }
                                            break;
                                    default:    
                                }
                                if (newli) {
                                    newli.addClass("hover");
                                    event.stopPropagation();
                                }
                            }
                        }
                        function flashit(selector) {
                            jQuery(selector).addClass('flash-effect');
                            setTimeout(function() {jQuery(selector).removeClass('flash-effect');},133);
                        }

                    </script>
                JS;
        return $script;
     }
    public function getmenutext($page_number){
        foreach ($this->menuitems as $item) {
            if ($item["page_number"]??"" == $page_number) {
                return $item["text"];
            } 
        }
        return "";
     }    
    private function test(){
    }
}