<?php
namespace App\Queries;

use DB;

class SalesReport
{
    protected $where;
    protected $bindings;

    public function __construct($dari, $sampai, $customer_id, $status)
    {
        $this->where = "WHERE 1=1";
        $this->bindings = [];

        if ($dari != '') {
            $this->where .= " AND o.tgl_order >= ?";
            $this->bindings[] = $dari . " 00:00:00";
        }
        if ($sampai != '') {
            $this->where .= " AND o.tgl_order <= ?";
            $this->bindings[] = $sampai . " 23:59:59";
        }
        if ($customer_id != '') {
            $this->where .= " AND o.customer_id = ?";
            $this->bindings[] = (int) $customer_id;
        }
        if ($status != '' && $status != 'semua') {
            $this->where .= " AND o.status = ?";
            $this->bindings[] = $status;
        }
    }

    public function getSummary()
    {
        // Missing o.status != 'deleted' intentionally, keeping existing behavior
        $sql = "SELECT COUNT(*) as jml_order, IFNULL(SUM(o.subtotal),0) as subtotal, IFNULL(SUM(o.diskon),0) as diskon, IFNULL(SUM(o.ppn),0) as ppn, IFNULL(SUM(o.total),0) as total FROM tbl_orders o " . $this->where;
        return DB::select($sql, $this->bindings)[0];
    }

    public function getPerHari()
    {
        $sql = "SELECT DATE(DATE_ADD(o.tgl_order, INTERVAL 7 HOUR)) as tgl, COUNT(*) as jml, SUM(o.total) as total FROM tbl_orders o " . $this->where . " AND o.status != 'deleted' GROUP BY DATE(DATE_ADD(o.tgl_order, INTERVAL 7 HOUR)) ORDER BY tgl DESC";
        return DB::select($sql, $this->bindings);
    }

    public function getPerStatus()
    {
        $sql = "SELECT o.status, COUNT(*) as jml, SUM(o.total) as total FROM tbl_orders o " . $this->where . " AND o.status != 'deleted' GROUP BY o.status";
        return DB::select($sql, $this->bindings);
    }

    public function getOrders()
    {
        $sql = "SELECT o.* FROM tbl_orders o " . $this->where . " AND o.status != 'deleted' ORDER BY o.tgl_order DESC";
        return DB::select($sql, $this->bindings);
    }

    public function getTopProduk()
    {
        $sql = "SELECT oi.product_id, SUM(oi.qty) as qty, SUM(oi.subtotal) as total FROM order_items oi JOIN tbl_orders o ON o.id = oi.order_id " . $this->where . " AND o.status != 'deleted' GROUP BY oi.product_id ORDER BY qty DESC LIMIT 10";
        return DB::select($sql, $this->bindings);
    }

    public function getTopCustomer()
    {
        $sql = "SELECT o.customer_id, COUNT(*) as jml, SUM(o.total) as total FROM tbl_orders o " . $this->where . " AND o.status != 'deleted' GROUP BY o.customer_id ORDER BY total DESC LIMIT 10";
        return DB::select($sql, $this->bindings);
    }

    public static function getExportCsvData($dari, $sampai)
    {
        $sql = "SELECT o.no_order, o.tgl_order, c.nama as customer, c.kota, u.name as sales, o.status, o.subtotal, o.diskon_persen, o.diskon, o.ppn, o.total, o.marketing_code
                FROM tbl_orders o
                LEFT JOIN tbl_customers c ON c.id = o.customer_id
                LEFT JOIN users u ON u.id = o.user_id
                WHERE o.status != 'deleted' ";
        
        $bindings = [];
        if ($dari) {
            $sql .= " AND o.tgl_order >= ? ";
            $bindings[] = $dari . " 00:00:00";
        }
        if ($sampai) {
            $sql .= " AND o.tgl_order <= ? ";
            $bindings[] = $sampai . " 23:59:59";
        }
        $sql .= " ORDER BY o.tgl_order DESC";
        
        return DB::select($sql, $bindings);
    }
}
