<?php
namespace app\controller\manager;
use \lib\StdLib as lib;
class ManagerCollection {
    private $trace = false;
	public function __construct(protected MenuManager $menumanager
                               ,protected LogManager $logmanager
                               ,protected ConfigManager $configmanager
                               ,protected UserManager $usermanager
                               ,protected ClientManager $clientmanager
                               ,protected TaskManager $taskmanager
                               ,protected RosterManager $rostermanager
                               ,protected RoleManager $rolemanager
                               ,protected PageManager $pagemanager
                               ,protected ActionManager $actionmanager
                               ,protected SessionManager $sessionmanager
                               ,protected EMailManager $emailmanager
                               ,protected ReportManager $reportmanager
                               ,protected StockCategoryManager $stockcategorymanager
                               ,protected StockManager $stockmanager
                               ,protected StocktakeManager $stocktakemanager
                               ,protected DeliveryManager $deliverymanager
                               ,protected StockoutManager $stockoutmanager
                               ,protected StockLevelReportManager $stocklevelreportmanager
                               ,protected DamagedStockManager $damagedstockmanager
                               ,protected StockUsageReportManager $stockusagereportmanager
                               ,protected LocationManager $locationmanager
                               ,protected StockSupplierManager $stocksuppliermanager
                               ,protected StockEventManager $stockeventmanager
                               ,protected StocktakeVarianceReportManager $stocktakevariancereportmanager
                               ,protected StockClientManager $stockclientmanager
                               ){
        if ($this->trace ) { echo "Visit ".__METHOD__."<br>\n"; }
	}
    public function TaskManager() {
        if ($this->trace ) { echo "Visit ".__METHOD__."<br>"; }
        return $this->taskmanager;
    }
    public function RosterManager() {
        if ($this->trace ) { echo "Visit ".__METHOD__."<br>"; }
        return $this->rostermanager;
    }
    public function LogManager() {
        if ($this->trace ) { echo "Visit ".__METHOD__."<br>"; }
        return $this->logmanager;
    }
    public function ConfigManager() {
        if ($this->trace ) { echo "Visit ".__METHOD__."<br>"; }
        return $this->configmanager;
    }
    public function UserManager() {
        if ($this->trace ) { echo "Visit ".__METHOD__."<br>"; }
        return $this->usermanager;
    }
    public function ClientManager() {
        if ($this->trace ) { echo "Visit ".__METHOD__."<br>"; }
        return $this->clientmanager;
    }
    public function Rolemanager() {
        if ($this->trace ) { echo "Visit ".__METHOD__."<br>"; }
        return $this->rolemanager;
    }
    public function MenuManager() {
        if ($this->trace ) { echo "Visit ".__METHOD__."<br>"; }
        return $this->menumanager;
    }        
    public function PageManager() {
        if ($this->trace ) { echo "Visit ".__METHOD__."<br>"; }
        return $this->pagemanager;
    }        
    public function ActionManager() {
        if ($this->trace ) { echo "Visit ".__METHOD__."<br>"; }
        return $this->actionmanager;
    }        
    public function SessionManager() {
        if ($this->trace ) { echo "Visit ".__METHOD__."<br>"; }
        return $this->sessionmanager;
    }
    public function EMailManager() {
        if ($this->trace ) { echo "Visit ".__METHOD__."<br>"; }
        return $this->emailmanager;
    }      
    public function ReportManager() {
        if ($this->trace ) { echo "Visit ".__METHOD__."<br>"; }
        return $this->reportmanager;
    }
    public function StockCategoryManager() {
        if ($this->trace ) { echo "Visit ".__METHOD__."<br>"; }
        return $this->stockcategorymanager;
    }
    public function StockManager() {
        if ($this->trace ) { echo "Visit ".__METHOD__."<br>"; }
        return $this->stockmanager;
    }
    public function StocktakeManager() {
        if ($this->trace ) { echo "Visit ".__METHOD__."<br>"; }
        return $this->stocktakemanager;
    }
    public function DeliveryManager() {
        if ($this->trace ) { echo "Visit ".__METHOD__."<br>"; }
        return $this->deliverymanager;
    }
    public function StockoutManager() {
        if ($this->trace ) { echo "Visit ".__METHOD__."<br>"; }
        return $this->stockoutmanager;
    }
    public function StockLevelReportManager() {
        if ($this->trace ) { echo "Visit ".__METHOD__."<br>"; }
        return $this->stocklevelreportmanager;
    }
    public function DamagedStockManager() {
        if ($this->trace ) { echo "Visit ".__METHOD__."<br>"; }
        return $this->damagedstockmanager;
    }
    public function StockUsageReportManager() {
        if ($this->trace ) { echo "Visit ".__METHOD__."<br>"; }
        return $this->stockusagereportmanager;
    }
    public function LocationManager() {
        if ($this->trace ) { echo "Visit ".__METHOD__."<br>"; }
        return $this->locationmanager;
    }
    public function StockSupplierManager() {
        if ($this->trace ) { echo "Visit ".__METHOD__."<br>"; }
        return $this->stocksuppliermanager;
    }
    public function StockEventManager() {
        if ($this->trace ) { echo "Visit ".__METHOD__."<br>"; }
        return $this->stockeventmanager;
    }
    public function StocktakeVarianceReportManager() {
        if ($this->trace ) { echo "Visit ".__METHOD__."<br>"; }
        return $this->stocktakevariancereportmanager;
    }
    public function StockClientManager() {
        if ($this->trace ) { echo "Visit ".__METHOD__."<br>"; }
        return $this->stockclientmanager;
    }
}
