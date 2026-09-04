<?php
namespace App\Services;

use App\Product;
use App\Exceptions\OrderValidationException;

class StockChecker
{
    public function checkAndPrepareItems(array $input, $user)
    {
        $product_id = isset($input['product_id']) ? $input['product_id'] : null;
        $qty = isset($input['qty']) ? $input['qty'] : null;
        $harga = isset($input['harga']) ? $input['harga'] : null;

        $flag = true;
        $errors = array();
        $data2 = array();
        $total_qty = 0;

        if (!is_array($product_id) || count($product_id) == 0) {
            $flag = false;
            $errors[] = 'Order harus ada minimal 1 item';
        } else {
            for ($i = 0; $i < count($product_id); $i++) {
                $pid = isset($product_id[$i]) ? $product_id[$i] : null;
                $q = isset($qty[$i]) ? $qty[$i] : null;
                $h = isset($harga[$i]) ? $harga[$i] : null;

                if ($pid == '' || $pid == null) {
                    continue;
                } else {
                    if (!is_numeric($pid)) {
                        $flag = false;
                        $errors[] = 'Produk baris ' . ($i + 1) . ' tidak valid';
                        continue;
                    } else {
                        $prod = Product::find($pid);
                        if (!$prod) {
                            $flag = false;
                            $errors[] = 'Produk baris ' . ($i + 1) . ' tidak ditemukan';
                            continue;
                        } else {
                            if ($prod->status != 'aktif') {
                                $flag = false;
                                $errors[] = 'Produk ' . $prod->nama_barang . ' sudah tidak aktif';
                                continue;
                            } else {
                                if ($q == '' || $q == null || !is_numeric($q)) {
                                    $flag = false;
                                    $errors[] = 'Qty produk ' . $prod->nama_barang . ' harus diisi';
                                    continue;
                                } else {
                                    if ($q <= 0) {
                                        $flag = false;
                                        $errors[] = 'Qty produk ' . $prod->nama_barang . ' harus lebih dari 0';
                                        continue;
                                    } else {
                                        if ($prod->stok < $q) {
                                            if ($user->type == 1) {
                                                $tmp = 'Stok ' . $prod->nama_barang . ' kurang (' . $prod->stok . '), order tetap disimpan (admin)';
                                                session()->flash('msg', $tmp);
                                            } else {
                                                $flag = false;
                                                $errors[] = 'Stok ' . $prod->nama_barang . ' tidak cukup (tersedia: ' . $prod->stok . ')';
                                                continue;
                                            }
                                        }

                                        if ($h == '' || $h == null) {
                                            $h = $prod->harga_jual;
                                        } else {
                                            $h = str_replace('.', '', $h);
                                            $h = str_replace(',', '', $h);
                                            if (!is_numeric($h)) {
                                                $h = $prod->harga_jual;
                                            } else {
                                                if ($h < $prod->harga_jual) {
                                                    if ($user->type != 1) {
                                                        $flag = false;
                                                        $errors[] = 'Harga ' . $prod->nama_barang . ' tidak boleh dibawah harga jual';
                                                        continue;
                                                    } else {
                                                        if ($h < $prod->harga_beli) {
                                                            $flag = false;
                                                            $errors[] = 'Harga ' . $prod->nama_barang . ' dibawah harga beli!';
                                                            continue;
                                                        }
                                                    }
                                                }
                                            }
                                        }

                                        $dup = false;
                                        foreach ($data2 as $k => $d) {
                                            if ($d['product_id'] == $pid) {
                                                $dup = true;
                                                $data2[$k]['qty'] = $data2[$k]['qty'] + $q;
                                                $data2[$k]['subtotal'] = $data2[$k]['qty'] * $data2[$k]['harga'];
                                                break;
                                            }
                                        }
                                        if (!$dup) {
                                            $data2[] = array(
                                                'product_id' => $pid,
                                                'nama' => $prod->nama_barang,
                                                'kode' => $prod->kode_barang,
                                                'satuan' => $prod->satuan,
                                                'qty' => (int) $q,
                                                'harga' => (float) $h,
                                                'subtotal' => (int) $q * (float) $h,
                                                'stok_sebelum' => $prod->stok,
                                            );
                                        }
                                        $total_qty = $total_qty + $q;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            if (count($data2) == 0 && $flag == true) {
                $flag = false;
                $errors[] = 'Order harus ada minimal 1 item';
            }
        }

        if (count($data2) > 50) {
            dd($data2);
        }

        if ($flag == false) {
            throw new OrderValidationException(implode('<br>', $errors));
        }

        return [
            'items' => $data2,
            'total_qty' => $total_qty
        ];
    }
}
