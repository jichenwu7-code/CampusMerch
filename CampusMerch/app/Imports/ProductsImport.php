<?php

namespace App\Imports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\Importable;

class ProductsImport implements ToModel, WithValidation
{
    use Importable;
    
    private $rowCount = 0;

    public function model(array $row)
    {
        $this->rowCount++;
        
        // 调试：查看实际接收到的数据
        logger()->info('Import row ' . $this->rowCount . ': ', $row);
        
        // 跳过标题行（第一行）
        if ($this->rowCount === 1) {
            logger()->info('Skipping header row');
            return null;
        }
        
        // 使用数字索引读取数据
        $name = $row[0] ?? null;
        
        // 如果商品名称为空，跳过此行
        if (empty($name)) {
            logger()->info('Skipping empty row ' . $this->rowCount);
            return null;
        }
        
        $product = new Product([
            'name' => $name,
            'category' => $row[1] ?? '',
            'price' => $row[2] ?? 0,
            'stock' => $row[3] ?? 0,
            'status' => $row[4] ?? 1,
            'cover_url' => $row[5] ?? null,
            'custom_rule' => $row[6] ?? null,
        ]);
        
        $product->save();
        logger()->info('Saved product: ' . $product->id . ' - ' . $product->name);
        
        return $product;
    }

    public function rules(): array
    {
        return [
            '*.商品名称' => 'nullable|string|max:255',
            '*.分类' => 'nullable|string|max:100',
            '*.价格' => 'nullable|numeric|min:0',
            '*.库存' => 'nullable|integer|min:0',
            '*.状态' => 'nullable|integer|in:0,1',
            '*.封面图' => 'nullable|string|max:500',
            '*.定制规则' => 'nullable|string|max:1000',
        ];
    }
    
    public function customValidationMessages()
    {
        return [
            '*.商品名称.required' => '商品名称不能为空',
            '*.分类.required' => '分类不能为空',
            '*.价格.required' => '价格不能为空',
            '*.价格.numeric' => '价格必须是数字',
        ];
    }

    public function getRowCount(): int
    {
        return $this->rowCount;
    }
}