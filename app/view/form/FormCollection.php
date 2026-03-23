<?php
namespace app\view\form;
use \lib\StdLib as lib;
class FormCollection {
    private $trace = false;
	public function __construct(protected LoginForm $loginform
                                ,protected ConfigForm $configform
                                ,protected RosterForm $rosterform
                                ,protected UserProfileForm $userprofileform
                                ,protected TaskForm $taskform
                                ,protected RoleForm $roleform
                                ,protected ReportForm $reportform
                                ,protected UserForm $userform
                                ,protected ClientAdminForm $clientadminform
                                ,protected ClientVolsForm $clientvolsform
                                ,protected ActionForm $actionform
                                ,protected PageForm $pageform
                                ,protected SessionForm $sessionform
                                ,protected AttendanceAdminForm $attendanceadminform
                                ,protected AttendanceVolsForm $attendancevolsform
                                ,protected AttendanceReportForm $attendancereportform
                                ,protected StartNewPasswordForm $startnewpasswordform
                                ,protected EnterNewPasswordForm $enternewpasswordform
                                ,protected MenuitemForm $menuitemform
                                ,protected ConfirmCodeForm $ConfirmCodeForm
                                ,protected StockCategoryForm $stockcategoryform
                                ,protected StockForm $stockform
                                ,protected StocktakeForm $stocktakeform
                                ,protected DeliveryForm $deliveryform
                                ,protected StockoutForm $stockoutform
                                ,protected StockLevelReportForm $stocklevelreportform
                                ,protected DamagedStockForm $damagedstockform
                                ,protected StockUsageReportForm $stockusagereportform
                                // ,protected SessionListForm $SessionList
                            ){
        if ($this->trace ) { echo "Enter ".__METHOD__."<br>\n"; }
	}
    public function LoginForm() {
        return $this->loginform;
    }
    public function ConfigForm() {
        return $this->configform;
    }
    public function MenuitemForm() {
        return $this->menuitemform;
    }
    public function StartNewPasswordForm() {
        return $this->startnewpasswordform;
    }
    public function EnterNewPasswordForm() {
        return $this->enternewpasswordform;
    }
    public function ConfirmCodeForm() {
        return $this->ConfirmCodeForm;
    }
    public function RosterForm() {
        return $this->rosterform;
    }
    public function UserProfileForm() {
        return $this->userprofileform;
    }
    public function ClientAdminForm() {
        return $this->clientadminform;
    }
    public function ClientVolsForm() {
        return $this->clientvolsform;
    }
    public function TaskForm() {
        return $this->taskform;
    }
    public function RoleForm() {
        return $this->roleform;
    }
    public function ReportForm() {
        return $this->reportform;
    }
    public function UserForm() {
        return $this->userform;
    }
     public function ActionForm() {
        return $this->actionform;
    }
     public function PageForm() {
        return $this->pageform;
    }
    public function AttendanceAdminForm() {
        return $this->attendanceadminform;
    }
    public function AttendanceVolsForm() {
        return $this->attendancevolsform;
    }
     public function AttendanceReportForm() {
        return $this->attendancereportform;
    }
    public function SessionForm() {
        return $this->sessionform;
    }
    public function StockCategoryForm() {
        return $this->stockcategoryform;
    }
    public function StockForm() {
        return $this->stockform;
    }
    public function StocktakeForm() {
        return $this->stocktakeform;
    }
    public function DeliveryForm() {
        return $this->deliveryform;
    }
    public function StockoutForm() {
        return $this->stockoutform;
    }
    public function StockLevelReportForm() {
        return $this->stocklevelreportform;
    }
    public function DamagedStockForm() {
        return $this->damagedstockform;
    }
    public function StockUsageReportForm() {
        return $this->stockusagereportform;
    }
}