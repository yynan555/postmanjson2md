<?php

$file = 'C:\Users\Administrator\Desktop\party_shool.postman_collection.json';
$generateRootDir = __DIR__.'/postmanjson2md_generate_files';

if (!is_dir($generateRootDir))
    mkdir ($generateRootDir,0777,true);

if (!is_file($file)) {
    echo '文件未找到: '.$file;
    return;
}

$data = file_get_contents($file);
$data = json_decode($data, true);

getItemMd($data, $generateRootDir);

function getItemMd($data, $generateRootDir, $name = '', $level = 0)
{
    $level ++;
    $result = [];
    if (!empty($data['item'])) {
        $name = $data['name'] ?? '';
        if ($name) {
            $_name = preg_match('/([a-zA-Z0-9_]+)/',$name,$a) ? $a[1] : $name;
            $generateRootDir .= '/'.$_name;
        }
        $r = getItemMd($data['item'], $generateRootDir, $name, $level);
        if ($r) $result[] = $r;
    } else if (!empty($data[0]['name'])) {
        foreach ($data as $item) {
            $r = getItemMd($item, $generateRootDir, $item['name'], $level);
            if ($r) $result[] = $r;
        }
    } else {
        $paramStr = '';
        if (!empty($data['request']['body']['formdata'])) {
            foreach ($data['request']['body']['formdata'] as $param) {
                $des = $param['description'] ?? '';
                $key = sprintf("%-10s", $param['key']);
                $paramStr .= <<<EOF
        - {$key} {$des} `{$param['value']}`

EOF;
            }
        }

        $exp = '';
        if (!empty($data['response'][0]['body'])) {
            $exp = $data['response'][0]['body'];
            $exp = str_replace("\n", "\n\t\t\t\t\t", $exp);
        }
        $itemContent = <<<EOF

- {$data['name']}
    - 请求方式: {$data['request']['method']}
    - 请求地址：`{$data['request']['url']}`
    - 参数：
{$paramStr}
    - 返回值
        - code != 1,提示msg信息
        - code == 1

                    {$exp}
EOF;

        return $itemContent;
    }

    if (!empty($result)) {
        if ($level < 3) {
            $file = $generateRootDir.'/other.md';
            return ; // 暂时不处理第一层的json数据
        } else {
            $file = $generateRootDir.'.md';
        }
        // 如果没有文件则创建文件
        if (!is_file($file)) {
            if (!is_dir(dirname($file))) {
                mkdir (dirname($file),0777,true);
            }
            $myfile = fopen($file, "w");
            fclose($myfile);

            $myfile = fopen($file, "a") or die("Unable to open file!");
            $txt = <<<EOF
## {$name}

EOF;
            fwrite($myfile, $txt);
            //记得关闭流
            fclose($myfile);
        }

        // 写入文件
        $myfile = fopen($file, "a") or die("Unable to open file!");
        foreach ($result as $itemStr) {
            $txt = <<<EOF
{$itemStr}

EOF;
            fwrite($myfile, $txt);
        }
        //记得关闭流
        fclose($myfile);
    }
}
