<?php
namespace app\view\body;
use \lib\StdLib as lib;
class StandardBody extends HTMLBody
{
// this is a generic body for a page that comprises just a form. It's used for MOST pages.
    protected $pagetitle;
    private $trace=false;
    public function render($pagenum,$rights=[],$isadmin=false,$menu="",$errormessage="",$trace=false,$subheading="",$stockalertpopup="")
    {
        // lib::pr($rights);
        if ($this->trace || $trace) { echo gtab(1)."Enter ".__METHOD__."<br>"; }
        $html = '<body>'."\n";
        $html .= '<div id="curtain"></div>';
        if ($errormessage !== "") {
            $html .= $this->renderdialog("Outcome:",$errormessage);
        }
        $html .= '<div id="volsdialog" style="min-width:600px;display:none;"></div>';
        if ($stockalertpopup !== '') {
            $html .= $stockalertpopup;
        }
        $html .= '<div id="content_panel" class="content_panel">'."\n";
        $html .= $this->form->render($pagenum,'',$subheading,$rights,$isadmin,$menu,$trace)."\n";
        $html .= $this->renderfooter();
        $html .= "</div><!--content_panel-->\n";
        $html .= "</body>\n";
        if ($this->trace || $trace) { echo gtab(-1)."Leave ".__METHOD__."<br>"; }
        return $html;
    }
}