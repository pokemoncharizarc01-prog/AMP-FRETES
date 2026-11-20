<?php
require_once __DIR__.'/config/conexao.php';
$data=$_GET['data']??date('Y-m-d');
$s=$pdo->prepare("SELECT f.codigo,f.data_frete,f.status,t.nome transportador,o.identificacao origem,d.identificacao destino,GROUP_CONCAT(CONCAT(p.nome,' (',fp.quantidade,')') SEPARATOR '; ') produtos FROM fretes f JOIN transportadores t ON t.id=f.transportador_id JOIN enderecos o ON o.id=f.origem_id JOIN enderecos d ON d.id=f.destino_id LEFT JOIN frete_produtos fp ON fp.frete_id=f.id LEFT JOIN produtos p ON p.id=fp.produto_id WHERE f.data_frete=? GROUP BY f.id ORDER BY f.codigo");
$s->execute([$data]);
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="amp-fretes-'.$data.'.csv"');
echo "\xEF\xBB\xBF";
$out=fopen('php://output','w');
fputcsv($out,['Código','Data','Status','Transportador','Origem','Destino','Produtos'],';');
while($r=$s->fetch()) fputcsv($out,[$r['codigo'],$r['data_frete'],$r['status'],$r['transportador'],$r['origem'],$r['destino'],$r['produtos']],';');
fclose($out);
