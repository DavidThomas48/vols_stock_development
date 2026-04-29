<?php
namespace app\view\head;
use \lib\StdLib as lib;
class HTMLHead extends \fw\view\head\HTMLHead
{
    private $trace = false;
    private $ajaxurl = "";
    private $pagenum = 0;
    private $targetpage = 0;
    private $session;
    private $multiselect;
    private $config;
    public function __construct(){
        if ($this->trace ) { echo "Enter ".__METHOD__."<br>\n"; }
     }
    public function init($session,$i="",$targetpage="") {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        $this->session = $session;
        $this->targetpage = $targetpage;
        $this->config = $session->getconfig();
        $this->ajaxurl = $this->config["app"]["SITEURL"] ?? '';
     }
    public function __destruct() {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
     }
    public function render($pagenum=0,$multiselect = false,$trace=false) {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        //echo "HTMLHead.Render. '",substr($formscript,0,50),"'\n";
        $this->pagenum  = $this->session->getpagenum(); 
        $this->multiselect = $multiselect ; 
        $this->settitle();
        $this->setmeta();
        $this->setlinks();
        $this->setstyle();
        $this->setscript();
        $head  = "<head>\n";
        $head .= $this->title.$this->meta.$this->links.$this->style.$this->script."\n"; 
        $head .= "</head>\n";
        if ($this->trace ) { echo "Leave ".__METHOD__."<br>\n"; }
        return $head;
     }
    protected function settitle() {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        if (array_key_exists("SITETITLE", $this->config["app"])) {
            $this->title = '<title>'.$this->config["app"]["SITETITLE"].'</title>'."\n";
        } else {
            $this->title = "";
        }
        if ($this->trace ) { echo "Leave ".__METHOD__."<br>\n"; }
     }
    protected function setmeta() {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        // $this->meta .= '<meta http-equiv="Content-Language" content="en-us">'."\n";
        $this->meta .= '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">'."\n";
        if ($this->trace ) { echo "Leave ".__METHOD__."<br>\n"; }
     }
    protected function setlinks() {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>\n"; }
        // $this->links .= '<link type="text/css" rel="stylesheet" href="jquery/jquery-ui.css">'."\n";
        // $this->links .= '<link type="text/css" rel="stylesheet" href="css/bootstrap.css" media="screen"/>'."\n";
        // $this->links .= '<link type="text/css" rel="stylesheet" href="css/jquery-ui.css" />'."\n";
        // $this->links .= '<link type="text/css" rel="stylesheet" href="css/jquery.tokenize.css" />'."\n";
        // $this->links .= '<link type="text/css" rel="stylesheet" href="css/jquery-ui.theme.css" />'."\n";
        // $this->links .= '<link type="text/css" rel="stylesheet" href="css/multiselect.css" media="screen">'."\n";
        // $this->links .= '<link type="text/css" rel="stylesheet" href="css/jquery.ui.timepicker.css" media="screen">'."\n";
        $this->links .= '<link type="text/css" rel="stylesheet" href="https://code.jquery.com/ui/1.13.3/themes/smoothness/jquery-ui.css">'."\n" ;
        $this->links .= '<link type="text/css" rel="stylesheet" href="app/assets/css/vols.0.2.css" >'."\n" ;
        $this->links .= '<link type="text/css" rel="stylesheet" href="app/assets/css/menu.0.2.css" >'."\n" ;
        $this->links .= '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" integrity="sha512-Fo3rlrZj/k7ujTnHg4CGR2D7kSs0v4LLanw2qksYuRlEzO+tcaEPQogQ0KaoGN26/zrn20ImR1DfuLWnOo7aBA==" crossorigin="anonymous" referrerpolicy="no-referrer">';
        if ((int) $this->pagenum < 100) {
            $this->links  .= '<link type="text/css" rel="stylesheet" href="app/assets/css/login.0.2.css" >';
        } else if ((int) $this->pagenum < 200) {
            $this->links  .= '<link type="text/css" rel="stylesheet" href="app/assets/css/roster.0.2.css" >';
            $this->links  .= '<link type="text/css" rel="stylesheet" href="app/assets/css/tables.0.2.css" >';
        } else if ((int) $this->pagenum == 332 || (int) $this->pagenum == 335 ) {
            $this->links  .= '<link type="text/css" rel="stylesheet" href="app/assets/css/keyboard.0.2.css" >';
            $this->links  .= '<link type="text/css" rel="stylesheet" href="app/assets/css/attendance.0.2.css" >';
        } else if ((int) $this->pagenum == 333 ) {
            $this->links  .= '<link type="text/css" rel="stylesheet" href="app/assets/css/tables.0.2.css" >';
            $this->links  .= '<link type="text/css" rel="stylesheet" href="app/assets/css/forms.0.2.css" >';
            $this->links  .= '<link type="text/css" rel="stylesheet" href="app/assets/css/attendancereports.0.2.css" >';
        } else {
            $this->links  .= '<link type="text/css" rel="stylesheet" href="app/assets/css/tables.0.2.css" >';
            $this->links  .= '<link type="text/css" rel="stylesheet" href="app/assets/css/forms.0.2.css" >';
        }
        if ($this->trace ) { echo "Leave ".__METHOD__."<br>\n"; }
     }
    protected function setscript() {
        if ($this->trace ) { echo "Enter ".__METHOD__."<br>"; }
        $dialog = $this->dialogscript();
        $ajax = $this->ajaxscript();
        $this->script = <<<JS
                            <script src="https://code.jquery.com/jquery-3.7.1.js"> </script> 
                            <script src="https://code.jquery.com/ui/1.13.3/jquery-ui.js"> </script>
                            <script>
                            let vols = {
                                cursor: {
                                    leavealone: false,
                                    wait:function () {
                                        if (!vols.cursor.leavealone) {
                                            document.body.style.cursor = 'wait';
                                            jQuery("body").addClass("waiting");
                                        }
                                    },
                                    default:function () {
                                        if (!vols.cursor.leavealone) {
                                            document.body.style.cursor = 'default'; 
                                            jQuery("body").removeClass("waiting");
                                        }
                                    },
                                },
                                enable: function (elements,pointer=0) {
                                    jQuery(elements).prop('disabled', false);
                                    jQuery(elements).css("pointer-events", "");
                                },
                                disable: function (elements, opacity) {
                                    if (opacity === undefined) {
                                        opacity = "0.5";
                                    }
                                    jQuery(elements).prop('disabled', true);
                                    jQuery(elements).css("pointer-events", "none");
                                    jQuery(elements).css("cursor","default");
                                    jQuery(elements).css("opacity", opacity);
                                },
                            };
                            {$dialog}
                            {$ajax}
                            function downloadTextFile(text, fileName) {
                                const anchor = document.createElement('a');
                                // Encode the text and prepend the data URL scheme
                                anchor.href = 'data:text/plain;charset=utf-8,' + encodeURIComponent(text);
                                anchor.download = fileName;
                                document.body.appendChild(anchor);
                                anchor.click();
                                document.body.removeChild(anchor);
                            }

                            </script>
                        JS;
        if ($this->trace) { echo "Leave ".__METHOD__."<br>"; }
     }
    protected function setstyle() {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        $this->style = '';
        if ($this->trace ) { echo "Leave ".__METHOD__."<br>\n"; }
     } 
    private function ajaxscript() {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        $script = <<<JS
            const doServerRequest = async function(id, thedata, target, options = {} ) {
                return new Promise((resolve, reject) => {
                    vols.cursor.wait();
            // jQuery("#kbdmsg").html(jQuery("#kbdmsg").html() + target + ": "+ thedata + "  id:  " +  + "<BR>");
                    jQuery.ajax({
                        type: "GET",
                        url: "{$this->ajaxurl}",
                        datatype: "text",
                        data: {
                            id: id,
                            thedata: thedata,
                            action_id: target,
                            ajax: 1,
                            cache: false
                        },
                        statusCode: {
                            400: function() {
                                vols.cursor.default();
                                vols.enable("body");
                                alert("ERROR 400 - ARE YOU STILL LOGGED INTO YOUR WEBSITE?");
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            vols.cursor.default();
                            vols.enable("body");
                            // console.log(textStatus + " " + errorThrown);
                            alert(textStatus+": "+errorThrown+" status= "+jqXHR.status+"  responseText: "+jqXHR.responseText);
            // jQuery("#kbdmsg").html( jQuery("#kbdmsg").html() +textStatus+": "+errorThrown+"<br>status= "+jqXHR.status+"<br>responseText"+jqXHR.responseText+"<br>");
                        },
                        success: function(response) {
            // jQuery("#kbdmsg").html( jQuery("#kbdmsg").html() + "response: " + response + "<BR>");
                            vols.cursor.default();
                            vols.enable("body");
                            if (response.startsWith("!!") || response.startsWith("Query Failed")) {
                                vols.enable("body");
                                response = response.startsWith("!!") ? response.substring(2) : response.replace("!!", "<br><br>");
                                jQuery.volsdialog("OKMSG", response, undefined, undefined, "Sorry, but something went wrong...");
                            } else {
                                switch (target) {
                                    case 'xxxx':
                                        break;
                                    default:
                                        resolve(response);
                                        break;
                                }
                            }
                        }
                    });
                });
            };
            // function formatErrorMessage(jqXHR, exception) {
            //     if (jqXHR.status === 0) {
            //         return ('Not connected. Please verify your network connection.[Error Code=0]');
            //     }
            //     else if (jqXHR.status == 400) {
            //         return ("Server understood the request but request content was invalid.[Error Code=400]");
            //     }
            //     else if (jqXHR.status == 401) {
            //         return ('Unauthorised access. [Error Code=401].');
            //     }
            //     else if (jqXHR.status == 403) {
            //         return ("Forbidden resouce can't be accessed.[Error Code=403]");
            //     }
            //     else if (jqXHR.status == 404) {
            //         return ('The requested page not found. [Error Code=404]');
            //     } else if (jqXHR.status == 500) {
            //         return ('Internal Server Error [Error Code=500].');

            //     }
            //     else if (jqXHR.status == 503) {
            //         return ('Service Unavailable. [Error Code=503]');
            //     }

            //     else if (exception === 'parsererror') {
            //         return ('Requested JSON parse failed.');
            //     } else if (exception === 'timeout') {
            //         return ('Time out error.');
            //     } else if (exception === 'abort') {
            //         return ('Ajax request aborted.');
            //     } else {
            //         return ('Uncaught Error.' + jqXHR.responseText);
            //     }
            // }
        JS;
        if ($this->trace ) { echo "Leave ".__METHOD__."<br>\n"; }
        return $script;
     }
    private function dialogscript() {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        $script = <<<JS

            jQuery.extend({ volsdialog :
               function (dtype, content, yesokfunction, nocancelfunction, title, buttons, options,okparam,onloadfunction,noparam='') {
                   let volsdialog = jQuery("#volsdialog"),
                       defaultoptions,
                       mybuttons,
                       myoptions,
                       classes;
                   if (volsdialog.length && (dtype === "CLOSE")) {
                       volsdialog.dialog("close");
                   } else {
                       defaultoptions = {id:"vols_dialog",
                                         autoOpen :  false,
                                         modal :  true,
                                         show :  true,
                                         minHeight: 300,
                                         maxHeight: 900,
                                         minWidth: 680,
                                         maxWidth: 1000,
                                         hide :  true,
                                         position: { my: "center", at: "center", of: window },
                                         };
                       // classes = { "ui-dialog" : "ui-corner-all vols-spellcheck-button",
                       //             "ui-dialog-titlebar" : "ui-corner-all",
                       //             "ui-button" : "ui-corner-all vols-spellcheck-button"
                       //          };
                       switch (dtype) {
                           case "BOOKINGHISTORY":
                           case "OKMSG":
                               if (!title) {
                                   title = "Just Letting you know...";
                               }
                               mybuttons = [{ text : "Close",
                                             "class": "vols-ok",
                                              click : function() {
                                                       jQuery(this).dialog("close");
                                                       const okp = okparam;
                                                       if (yesokfunction !== undefined) {
                                                         yesokfunction(okp);
                                                       }
                                                    }
                                         }];
                               if (dtype === "BOOKINGHISTORY" ) {
                                   myoptions = jQuery.extend(options, {Width: "auto", minWidth: 900, maxWidth: "95vw", height:"auto" });
                               } else {
                                   myoptions = jQuery.extend(options, {Width: "auto", minWidth: 450, maxWidth: "95vw", height:"auto" });
                               }
                               break;
                            // case "PRINT":
                            // case "REVIEW" :""
                            //     if (!title) {
                            //        title = "Reciprocal Clubs";
                            //     }
                            //     if (dtype==="PRINT") {
                            //       myoptions = {maxWidth: 900,width : 900, dialogClass: "no-close"};
                            //     } else {
                            //       myoptions = {maxWidth: 600,width : "auto", dialogClass: "no-close"};
                            //     }
                            //     // no break here on purpose
                            // case "AREYOUSURE":
                            //     if (!title) {
                            //         title = "Just checking...";
                            //     }
                            //     mybuttons = [{ text : (dtype==="PRINT"?"Print":dtype==="REVIEW"?"Submit":"Yes"),
                            //                  "class": "vols-spellcheck-button vols-ok",
                            //                   click : function() {
                            //                             let proceed = 1;
                            //                             if (dtype === "REVIEW") {
                            //                                 proceed = volsValidateReview();
                            //                             }
                            //                             if (proceed) {
                            //                                 if (dtype !== "PRINT") {
                            //                                     jQuery(this).dialog("close");
                            //                                 }
                            //                                 if (yesokfunction !== undefined) {
                            //                                     yesokfunction(okparam);
                            //                                 }
                            //                            }
                            //                        }
                            //                },
                            //                {text: dtype==="PRINT"?"Close":"Cancel",
                            //                     "class": "vols-spellcheck-button vols-cancel",
                            //                     click : function() {
                            //                             jQuery(this).dialog("close");
                            //                                 if (nocancelfunction !== undefined) {
                            //                                     nocancelfunction(okparam);
                            //                                 }
                            //                             }
                            //                 }
                            //              ];
                            //    break;
                            case "YESNO":
                                if (!title) {
                                   title = "Please confirm...";
                                }
                                mybuttons = [{ text : "Yes",
                                                "class": "vols-spellcheck-button vols-ok",
                                                click : function() {
                                                           jQuery(this).dialog("close");
                                                           const okp = okparam;
                                                           if (yesokfunction !== undefined) {
                                                             yesokfunction(okp);
                                                           }
                                                         }
                                               },
                                             {text: "Cancel",
                                                "class": "vols-spellcheck-button vols-cancel",
                                                click : function() {
                                                         jQuery(this).dialog("close");
                                                         const nop = noparam;
                                                         if (nocancelfunction !== undefined) {
                                                           nocancelfunction(nop);
                                                         }
                                                       }
                                                }
                                         ];
                                options = jQuery.extend(options, {minWidth : 600, dialogClass: "no-close",});
                               break;
                            // case "TEXTAREAEDIT":
                            //    mybuttons = [{ text : "Save",
                            //                   "class": "vols-spellcheck-button vols-ok",
                            //                   click: function() {
                            //                            const data = $(this).dialog().find("textarea").val();
                            //                            const okp = okparam;
                            //                            jQuery(this).dialog("close");
                            //                            if (yesokfunction !== undefined) {
                            //                              yesokfunction(okp,data);
                            //                            }
                            //                          }
                            //                },
                            //                {text: "Cancel",
                            //                  "class": "vols-spellcheck-button vols-cancel",
                            //                  click: function() {
                            //                            jQuery(this).dialog("close");
                            //                          }
                            //                }
                            //              ];
                            //    options = jQuery.extend(options, {width :  "auto", height :  "auto",dialogClass: "no-close",});
                            //    break;
                            // case "ITEM":
                            //    if (!title) {
                            //        title = "Item Details";
                            //    }
                            //    mybuttons = [{ text: "Close",
                            //                   click : function() {
                            //                              if (yesokfunction !== undefined) {
                            //                                  yesokfunction();
                            //                              }
                            //                              jQuery(this).dialog("close");
                            //                           }
                            //              }];
                            //    options = jQuery.extend(options, {minWidth: 400, width : "auto", height :  "auto"});
                            //    classes = {  "ui-dialog" : "ui-corner-all vols-spellcheck-button ",
                            //                 "ui-dialog-titlebar" : "ui-corner-all",
                            //                 "ui-button" : "ui-corner-all vols-spellcheck-button",
                            //                 "ui-dialog-content" : "vols-font-8px"};
                            //    break;
                            // case "SPELLCHECK":
                            //    if (!title) {
                            //        title = "Just checking...";
                            //    }
                            //    mybuttons = [{ text: "Search for suggestion",
                            //                  "class": "vols-spellcheck-button",
                            //                  click : function() {
                            //                    vols.spellcheck = 0;
                            //                    jQuery("#opac-title").val(jQuery("#spellcheck-title").html());
                            //                    jQuery("#opac-name").val(jQuery("#spellcheck-name").html());
                            //                    jQuery("#opac-subject").val(jQuery("#spellcheck-subject").html());
                            //                    jQuery("#opac-series").val(jQuery("#spellcheck-series").html());
                            //                    jQuery("#opac-keyword").val(jQuery("#spellcheck-keyword").html());
                            //                    jQuery(this).dialog("close");
                            //                    volsExecuteTheSearch("basesearch",0,"");
                            //                  }
                            //                },
                            //                {text: "Cancel",
                            //                  "class": "vols-spellcheck-button vols-cancel",
                            //                  click : function() {
                            //                    jQuery(this).dialog("close");
                            //                    vols.enable("body");
                            //                  }
                            //                }
                            //              ];
                            //    options = jQuery.extend(options, {width :  "auto", height :  "auto",});
                            //    break;
                            default:
                               mybuttons =  [{text : "OK", click : function() {
                                               jQuery(this).dialog("close");
                                             }
                                           }];
                       }
                       if (options) {
                           defaultoptions = jQuery.extend(defaultoptions, options);
                       }
                       if (myoptions) {
                           defaultoptions = jQuery.extend(defaultoptions, myoptions);
                       }
                       if (volsdialog.length < 1) { // create the dialog DIV
                           volsdialog = jQuery("<div/>").attr("id", "volsdialog").appendTo("body");
                       // } else {
                           // volsdialog.dialog("destroy");
                       }
                       // put it all together
                       volsdialog.html(content).dialog(defaultoptions).dialog("option", "title", title);
                       if (mybuttons) { // there are buttons
                           volsdialog.dialog("option", "buttons", mybuttons);
                       }
                       volsdialog.dialog( "option", "minWidth", 600 );
                       // and launch
                       volsdialog.dialog("open");
                       if (onloadfunction !== undefined) {
                           onloadfunction();
                       }
                       jQuery(window).resize(function() {
                         volsdialog.dialog("option", "position", { my: "center", at: "center", of: window });
                         volsdialog.dialog("option", "width", "auto");
                       });
                   }
               }
            });
        JS;
        if ($this->trace ) { echo "Leave ".__METHOD__."<br>\n"; }
        return $script;
     }
}
//        $script  .= '<script src="vendor\tocca\Tocca.js" ></script>'."\n"; 
        // $script  = '<script src="app/assets/js/vols.js"></script>'."\n";
        // $script .= '<script  type="text/javascript">'."\n";  
        // $script .= 'var targetpage = 0;'."\n";
        // $script .= 'var cntrlispressed= false;'."\n";
        // $script .= '$(document).ready(function(){'."\n";
//         $script .= '    $(document).tooltip();'."\n";
//         $script .= '    $("#tokenize").tokenize();'."\n";
//         $script .= '    $("#close_popup").click(function(){'."\n";
//         $script .= '     hidediv("popupmsg");'."\n";
//         $script .= '    });'."\n";
//         $script .= '    $("#menuitem_LOGIN").click(function(){'."\n";
//         $script .= '     showloginpopup();'."\n";
//         $script .= '    });'."\n";
//         $script .= '    $(document).keydown(function(event){'."\n";
//         $script .= '        cntrlispressed= (event.which=="17");'."\n";
//         $script .= '    });'."\n";
//         $script .= '    $(document).keyup(function(){'."\n";
//         $script .= '        cntrlispressed= false;'."\n";
//         $script .= '    });'."\n";
//         $script .= '    $("#body").click(function(){'."\n";
//         $script .= '    hidediv("popupmsg");'."\n";
//         $script .= '    });'."\n";
//         $script .= '    $("#menuitem_MYACCOUNT").mouseleave(function(e){'."\n";
//         $script .= '       var $this = $(this);'."\n";
//         $script .= '       var bottom = $this.offset().top + $this.outerHeight() -1;'."\n";
//         $script .= '       if(e.pageY < bottom)  {'."\n";
//         $script .= '           hidediv("pop_menu");'."\n";
//         $script .= '       };'."\n";
//         $script .= '    });'."\n";
//         $script .= '    $("#pop_menu").mouseleave(function(e){'."\n";
//         $script .= '       var $this = $(this);'."\n";
//         $script .= '       if(e.pageY >= $this.offset().top)  {'."\n";
//         $script .= '           hidediv("pop_menu");'."\n";
//         $script .= '       };'."\n";
//         $script .= '    });'."\n";
//         $script .= '    $("#custpop_menu").mouseleave(function(e){'."\n";
//         $script .= '       var $this = $(this);'."\n";
//         $script .= '       var bottom = $this.offset().top + $this.outerHeight();'."\n";
//         $script .= '       if(e.pageY < bottom)  {'."\n";
//         $script .= '           hidediv("custpop_menu");'."\n";
//         $script .= '       };'."\n";
//         $script .= '    });'."\n";
//         $script .= '    $("#syspop_menu").mouseleave(function(e){'."\n";
//         $script .= '       var $this = $(this);'."\n";
//         $script .= '       var bottom = $this.offset().top + $this.outerHeight();'."\n";
//         $script .= '       if(e.pageY < bottom)  {'."\n";
//         $script .= '           hidediv("syspop_menu");'."\n";
//         $script .= '       };'."\n";
//         $script .= '    });'."\n";
//         $script .= '    $("#menuitem_CUSTOMERSETUP").mouseleave(function(e){'."\n";
//         $script .= '       var $this = $(this);'."\n";
//         $script .= '       if(e.pageY >= $this.offset().top)  {'."\n";
//         $script .= '           hidediv("custpop_menu");'."\n";
//         $script .= '       };'."\n";
//         $script .= '    });'."\n";
//         $script .= '    $("#menuitem_SYSTEMSETTINGS").mouseleave(function(e){'."\n";
//         $script .= '       var $this = $(this);'."\n";
//         $script .= '       if(e.pageY >= $this.offset().top)  {'."\n";
//         $script .= '           hidediv("syspop_menu");'."\n";
//         $script .= '       };'."\n";
//         $script .= '    });'."\n";
//         $script .= '    $("#menuitem_MYACCOUNT").mouseenter(function(){'."\n";
//         $script .= '       hidediv("syspop_menu");'."\n";
//         $script .= '       hidediv("custpop_menu");'."\n";
//         $script .= '       showdiv("pop_menu");'."\n";
//         $script .= '    });'."\n";
//         $script .= '    $("#menuitem_CUSTOMERSETUP").mouseenter(function(){'."\n";
//         $script .= '       hidediv("pop_menu");'."\n";
//         $script .= '       hidediv("syspop_menu");'."\n";
//         $script .= '       showdiv("custpop_menu");'."\n";
//         $script .= '    });'."\n";
//         $script .= '    $("#menuitem_SYSTEMSETTINGS").mouseenter(function(){'."\n";
//         $script .= '       hidediv("pop_menu");'."\n";
//         $script .= '       hidediv("custpop_menu");'."\n";
//         $script .= '       showdiv("syspop_menu");'."\n";
//         $script .= '    });'."\n";
//         $script .= '    $.extend({'."\n";
//         $script .= '        cpdialog: function (dtype , content, yesokfunction, nocancelfunction,title, buttons, options) {'."\n";
//                         //=========================================================
//                         // <summary>Utility to show a dialog on the page. buttons and options are optional.</summary>'."\n";
//                         // <param name="buttons" type="Object">Dialog buttons. Optional, defaults to single OK button.</param>'."\n";
//                         // <param name="options" type="Object">Additional jQuery dialog options. Optional.</param>'."\n";
//         $script .= '           var pd = $("#cp-dialog");'."\n";
//         $script .= '           if (pd.length && (dtype == "CLOSE")) {'."\n"; 
//         $script .= '                pd.dialog("close");'."\n";
//         $script .= '           } else { '."\n";
//         $script .= '             var defaultoptions = {'."\n";
//         $script .= '                    dialogClass: "cp-no-close",'."\n";
//         $script .= '                    autoOpen: false,'."\n";
//         $script .= '                    modal: true,'."\n";
//         $script .= '                    show: true,'."\n";
//         $script .= '                    hide: true,'."\n";
//         $script .= '                    width: 400'."\n";
//         $script .= '             };'."\n";
//         $script .= '             var bt;    '."\n";
//         $script .= '             if (dtype == "PLEASEWAIT") {'."\n"; // no buttons required
//         $script .= '                  if (!(title)) title = "Please wait...";'."\n";
//         $script .= '             } else if (dtype == "AREYOUSURE") {'."\n";
//         $script .= '                 if (!(title)) title = "Are you sure?";'."\n";
//         $script .= '                 bt = [{text: "Yes",click: function () {'."\n";
//         $script .= '                        if (!(yesokfunction === undefined)) yesokfunction(); '."\n";
//         $script .= '                           $(this).dialog("close");'."\n";
//         $script .= '                        }},'."\n";
//         $script .= '                       {text: "No" ,click: function () {'."\n";
//         $script .= '                        if (!(nocancelfunction === undefined)) nocancelfunction(); '."\n";
//         $script .= '                           $(this).dialog("close");'."\n";
//         $script .= '                       }}];'."\n";
//         $script .= '             } else if (dtype == "LOGIN") {'."\n";
//         $script .= '                 if (!(title)) title = "Login";'."\n";
//         $script .= '                 bt = [{text: "Login",click: function () {'."\n";
//         $script .= '                          if (!(yesokfunction === undefined)) yesokfunction(); '."\n";
//         $script .= '                       }},'."\n";
//         $script .= '                       {text: "Cancel",click: function () {'."\n";
//         $script .= '                          if (!(nocancelfunction === undefined)) nocancelfunction(); '."\n";
//         $script .= '                          $(this).dialog("close");'."\n";
//         $script .= '                       }}];'."\n";
//         $script .= '                 width = "400";height ="auto";'."\n";
//         $script .= '             } else { '."\n";
//         $script .= '                  bt = [{text: "OK",click: function () {$(this).dialog("close");}}];'."\n";
//         $script .= '             } '."\n";
//         $script .= '             if (options)'."\n";  
//         $script .= '                defaultoptions = $.extend(defaultoptions , options);'."\n";
//         $script .= '             if (pd.length < 1)'."\n";
//         $script .= '               pd = $("<div/>").attr("id", "cp-dialog")'."\n";
//         $script .= '                               .appendTo("body");'."\n";
//         $script .= '             else'."\n";
//         $script .= '                pd.dialog("destroy");'."\n";
//         $script .= '             pd.html(content)'."\n";
//         $script .= '               .dialog(defaultoptions)'."\n";
//         $script .= '               .dialog("option", "title",title)'."\n";
//         $script .= '             if (bt) {'."\n";
//         $script .= '               pd.dialog("option", "buttons",bt)};'."\n";
//         $script .= '               pd.dialog("open");'."\n";
//         $script .= '             }'."\n";
//         $script .= '        }'."\n";
//         $script .= '    })//end of extend Argument'."\n";
//         if ($this->session->isloggedin()) {
//           $script .=  "    hidediv('menuitem_LOGIN');\n";
//           $script .=  "    hidediv('menuitem_SIGNUP');\n";
//         } else {
//           $script .=  "    hidediv('menuitem_MYACCOUNT');\n";
//           $script .=  "    hidediv('menuitem_LOGOUT');\n";
//           $script .=  "    hidediv('loggedinusername');\n";
//         }
//         if ($norights) { 
//             $script .= '    console.log ("no rights");'."\n";
//             $heading = 'Sorry'.(($this->session->isloggedin($name,$contact,$pos,$isperson))?(' '.$name):'').",";
//             $script .= "    showmessage('".$heading."','You do not have permission to visit that page.')\n";
//         } else if ($this->session->loginhasexpired($name)) {
//             $script .= '    console.log ("expired");'."\n";
//             $script .=  "    setnextpage(".$nextpage_num.");\n";
//             $script .=  "    showloginpopup(true,'".$name."');\n" ; 
//         } else if ($loginrequired) {
//             $script .= '    console.log ("login required");'."\n";
//             $script .=  "    setnextpage(".$nextpage_num.");\n";
//             $script .=  "    showloginpopup();";
//         } else {
//             $script .= '    console.log ("all good");'."\n";
//         } 

//         $script .= '});'."\n";
//         $script .= 'function setnextpage(nextpage){'."\n";
//         $script .= '   targetpage = nextpage;'."\n";
//         $script .= '};'."\n";
// //        $script .= '$(document).keypress(function(e) {'."\n";
// //        $script .= '    if((e.keyCode == 13) && $(".ui-dialog").visibility == "visible"))   '."\n";
// //        $script .= '    {'."\n";
// //        $script .= '      ProcessLogin(); '."\n";
// //        $script .= '    }'."\n";
// //        $script .= '  });'."\n";
//         $script .= 'function loginsubmit()'."\n";
//         $script .= '{'."\n";
//         $script .= '   $("#cp-dialog #cp-loginform #cp-login-result").html("");'."\n";
//         $script .= '   $("#cp-dialog #cp-loginform #cp-login-reason").html("");'."\n";
//         $script .= '   ProcessLogin();'."\n"; 
//         $script .= '}'."\n";
//         $script .= 'function showmessage(heading,message)'."\n";
//         $script .= '{'."\n";
//         $script .= '   $.cpdialog("OK",message,"","",heading);'."\n";        $script .= '}'."\n";
//         $script .= 'function showdiv(divid)'."\n";
//         $script .= '{'."\n";
//         $script .= '   $("#"+divid).fadeIn();'."\n";
//         $script .= '   $("#"+divid).css({"visibility":"visible","display":"block"});'."\n";
//         $script .= '}'."\n";
//         $script .= 'function hidediv(divid)'."\n";
//         $script .= '{'."\n";
//         $script .= '   $("#".concat(divid)).fadeOut();'."\n";
//         $script .= '   $("#".concat(divid)).css({"visibility":"hidden","display":"none"});'."\n";
//         $script .= '}'."\n";
//         $script .= 'function showclass(classid)'."\n";
//         $script .= '{'."\n";
//         $script .= '   $("."+classid).fadeIn();'."\n";
//         $script .= '   $("."+classid).css({"visibility":"visible","display":"block"});'."\n";
//         $script .= '}'."\n";
//         $script .= 'function hideclass(classid)'."\n";
//         $script .= '{'."\n";
//         $script .= '   $(".".concat(classid)).fadeOut();'."\n";
//         $script .= '   $(".".concat(classid)).css({"visibility":"hidden","display":"none"});'."\n";
//         $script .= '}'."\n";
//         $script .= 'function go_loggedin(loggedinname,popmenu)'."\n";
//         $script .= '{'."\n";
//         $script .= '    hidediv("menuitem_LOGIN");'."\n";
//         $script .= '    hidediv("menuitem_SIGNUP");'."\n";
//         $script .= '    showdiv("menuitem_MYACCOUNT");'."\n";
//         $script .= '    showdiv("menuitem_LOGOUT");'."\n";
//         $script .= '    $("#loggedinusername").html("Logged in as ".concat(loggedinname));'."\n";
//         $script .= '    showdiv("loggedinusername");'."\n";
//         $script .= '    $("#menuitem_SIGNUP").css({"border-right":"3px double #fff"});'."\n";
//         $script .= '    $("#pop_menu").html(popmenu);'."\n";
//         $script .= '    $("#menuitem_MYACCOUNT").mouseenter(function(){'."\n";
//         $script .= '        showdiv("pop_menu");'."\n";
//         $script .= '    });'."\n";
//         $script .= '}'."\n";
// //=========================================================
//         $script .= 'function showloginpopup(hasexpired=false,username)'."\n";
//         $script .= '{'."\n";
//         $script .= '   $("#cp-dialog #cp-loginform #cp-login-reason").html("");'."\n";
//         $script .= '   $("#cp-dialog #cp-loginform #loginname").val("");'."\n";
//         $script .= '   $("#cp-dialog #cp-loginform #password").val("");'."\n";
//         $script .= '   $.cpdialog("LOGIN",$("#loginform").html(),ProcessLogin);'."\n"; 
//         $script .= '   if (hasexpired) {'."\n";
//         $script .= '       $("#cp-dialog #cp-loginform #cp-login-reason").html("Sorry ".concat(username,", your login has timed out due to inactivity. Please login again.<br>&nbsp;"));'."\n";
//         $script .= '       $("#cp-dialog #cp-loginform #cp-login-message").css("display","none");'."\n";
//         $script .= '   } else {'."\n";
//         $script .= '       $("#cp-dialog #cp-loginform #cp-login-reason").html("");'."\n";
//         $script .= '       $("#cp-dialog #cp-loginform #cp-login-message").css("display","block");'."\n";
//         $script .= '   }'."\n";
//         $script .= '}'."\n";
// //=========================================================sd
//         $script .= 'function ProcessLogin() {'."\n";
//         $script .= '    var loginval    = $("#cp-dialog #cp-loginform #cp-loginname").val();'."\n";//.trim()
//         $script .= '    var passwordval = $("#cp-dialog #cp-loginform #cp-password").val();'."\n"; //.trim()
//         $script .= '    hideclass("ui-dialog-buttonset");'."\n";
//         $script .= '    $("#cp-dialog #cp-loginform #cp-login-reason").html("");'."\n";
//         $script .= '    $("#cp-dialog #cp-loginform #cp-login-result").html("");'."\n";
//         $script .= '    if ((loginval.length == 0) || (passwordval.length == 0)){ '."\n";
//         $script .= '        $("#cp-dialog #cp-loginform #cp-login-message").html("Please supply a User ID and a Password");'."\n";
//         $script .= '    } else {'."\n";
//         $script .= '        console.log("process");'."\n";
//         $script .= '            var frm = $("#cp-dialog #cp-loginform");'."\n";
//         $script .= '            var frmdata = frm.serialize();'."\n";
//         $script .= '            console.log("submit:  ".concat(frmdata));'."\n";
//         $script .= '            $.ajax({'."\n";
//         $script .= '                type: frm.attr(\'method\'),'."\n";
//         $script .= '                url: frm.attr(\'action\'),'."\n";
//         $script .= '                data: frm.serialize(),'."\n";
//         $script .= '                success: function (newdata) {'."\n"; 
//         $script .= '                    console.log("Success:  ".concat(newdata));'."\n";
//         $script .= '                    if (newdata.indexOf("Success") == 0){'."\n";
//         $script .= '                        var loggedinname =  "";'."\n";
//         $script .= '                        var popmenu =  "";'."\n";
//         $script .= '                        var syspopmenu =  "";'."\n";
//         $script .= '                        if (newdata.indexOf(" ") >-1) {'."\n";
//         $script .= '                            loggedinname = newdata.slice(newdata.indexOf(" ")+1);'."\n";
//         $script .= '                            popmenu = loggedinname.slice(loggedinname.indexOf("||")+2);'."\n";
//         $script .= '                            syspopmenu = popmenu.slice(popmenu.indexOf("||")+2);'."\n";
//         $script .= '                            popmenu = popmenu.slice(0,popmenu.indexOf("||")).trim();'."\n";
//         $script .= '                            loggedinname = loggedinname.slice(0,loggedinname.indexOf("||")).trim();'."\n";
//         $script .= '                        }'."\n";
//         $script .= '                        if (targetpage != 0) {'."\n";
//         $script .= '                            var targeturl = "'.$protocol.$siteglobals["HOMEPAGE"].'?p="'."\n";
//         $script .= '                            var targeturl = targeturl.concat(targetpage,"'.'&pp='.$this->pagenum.'");'."\n";
//         $script .= '                            window.location.href = targeturl;'."\n";
//         $script .= '                        } else {'."\n";
//         $script .= '                            go_loggedin(loggedinname,popmenu,syspopmenu);'."\n";
//         $script .= '                            $.cpdialog("CLOSE");'."\n";
//         $script .= '                        }'."\n";
//         $script .= '                    } else if (newdata == "Failed") {'."\n";
//         $script .= '                        showclass("ui-dialog-buttonset");'."\n";
//         $script .= '                        console.log("Failed:  ".concat(newdata));'."\n";
//         $script .= '                        $("#cp-dialog #cp-loginform #cp-login-result").html("Sorry.<br>That User ID & Password combination was not found.<BR>Please try again.");'."\n"; //#cp-dialog #cp-loginform 
//         $script .= '                    } else {'."\n";
//         $script .= '                        showclass("ui-dialog-buttonset");'."\n";
//         $script .= '                        $("#cp-dialog #cp-loginform #cp-login-result").html("Unexpected result:  ".concat(newdata))}'.";\n"; //
//         $script .= '                },'."\n";
//         $script .= '                error: function (xobj,mystatus,myerror) {'."\n";
//         $script .= '                    console.log(mystatus.concat(" :  ",myerror));'."\n";
//         $script .= '                    $("#cp-dialog #cp-loginform #cp-login-result").html("Unexpected result:".concat(mystatus,":  ",myerror))'.";\n";  //#cp-dialog #cp-loginform 
//         $script .= '                },'."\n";
//         $script .= '                beforeSend: function (xobj,settings) {'."\n";
//         $script .= '                    console.log("beforeSend:  AJAX sending");'."\n";
//         $script .= '                },'."\n";
//         $script .= '                complete: function (xobj,mystatus) {'."\n";
//         $script .= '                    console.log("AJAX complete: ".concat(mystatus));'."\n";
//         $script .= '                }'."\n";
        // $script .= '            })'."\n";
/*
ajaxStart (Global Event)   This event is triggered if an Ajax request is started and no other Ajax requests are currently running.
beforeSend (Local Event)   This event, which is triggered before an Ajax request is started, allows you to modify the XMLHttpRequest object (setting additional headers, if need be.)
ajaxSend (Global Event)    This global event is also triggered before the request is run.
success (Local Event)      This event is only called if the request was successful (no errors from the server, no errors with the data).
ajaxSuccess (Global Event) This event is also only called if the request was successful.
error (Local Event)        This event is only called if an error occurred with the request (you can never have both an error and a success callback with a request).
ajaxError (Global Event)   This global event behaves the same as the local error event.
complete (Local Event)     This event is called regardless of if the request was successful, or not. You will always receive a complete callback, even for synchronous requests.
ajaxComplete (Global Event)This event behaves the same as the complete event and will be triggered every time an Ajax request finishes.
ajaxStop (Global Event)    This global event is triggered if there are no more Ajax requests being processed.
*/
        // $script .= '            console.log("end submit");'."\n";
        // $script .= '    };'."\n";
        // $script .= '}'."\n";
        // $script .= $formscript;
