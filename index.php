<?php
require_once __DIR__ . '/config/conexao.php';
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
$page = $_GET['page'] ?? 'dashboard';
$allowed = ['dashboard','produtos','enderecos','transportadores','fretes','novo_frete','relatorios'];
if (!in_array($page, $allowed, true)) $page = 'dashboard';

function countRows(PDO $pdo, string $table): int {
    return (int)$pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
}
function redirectTo(string $page, string $msg='Salvo com sucesso!'): void {
    header('Location: index.php?page='.$page.'&msg='.urlencode($msg)); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'produto') {
            $id=(int)($_POST['id']??0);
            $data=[trim($_POST['nome']??''),trim($_POST['descricao']??''),(float)($_POST['peso']??0),(float)($_POST['valor']??0)];
            if ($data[0]==='') throw new Exception('Informe o nome do produto.');
            if ($id) { $s=$pdo->prepare('UPDATE produtos SET nome=?,descricao=?,peso=?,valor=? WHERE id=?'); $s->execute([...$data,$id]); }
            else { $s=$pdo->prepare('INSERT INTO produtos(nome,descricao,peso,valor) VALUES(?,?,?,?)'); $s->execute($data); }
            redirectTo('produtos','Produto salvo com sucesso!');
        }
        if ($action === 'endereco') {
            $id=(int)($_POST['id']??0);
            $data=[trim($_POST['identificacao']??''),trim($_POST['logradouro']??''),trim($_POST['numero']??''),trim($_POST['bairro']??''),trim($_POST['cidade']??''),strtoupper(trim($_POST['estado']??'')),trim($_POST['cep']??''),trim($_POST['complemento']??'')];
            if (!$data[0] || !$data[1] || !$data[4] || strlen($data[5])!==2) throw new Exception('Preencha identificação, logradouro, cidade e UF (2 letras).');
            if ($id) { $s=$pdo->prepare('UPDATE enderecos SET identificacao=?,logradouro=?,numero=?,bairro=?,cidade=?,estado=?,cep=?,complemento=? WHERE id=?'); $s->execute([...$data,$id]); }
            else { $s=$pdo->prepare('INSERT INTO enderecos(identificacao,logradouro,numero,bairro,cidade,estado,cep,complemento) VALUES(?,?,?,?,?,?,?,?)'); $s->execute($data); }
            redirectTo('enderecos','Endereço salvo com sucesso!');
        }
        if ($action === 'transportador') {
            $id=(int)($_POST['id']??0);
            $data=[trim($_POST['nome']??''),trim($_POST['documento']??''),trim($_POST['telefone']??''),trim($_POST['email']??''),trim($_POST['tipo_veiculo']??'')];
            if (!$data[0]) throw new Exception('Informe o nome do transportador.');
            if ($id) { $s=$pdo->prepare('UPDATE transportadores SET nome=?,documento=?,telefone=?,email=?,tipo_veiculo=? WHERE id=?'); $s->execute([...$data,$id]); }
            else { $s=$pdo->prepare('INSERT INTO transportadores(nome,documento,telefone,email,tipo_veiculo) VALUES(?,?,?,?,?)'); $s->execute($data); }
            redirectTo('transportadores','Transportador salvo com sucesso!');
        }
        if ($action === 'frete') {
            $origem=(int)($_POST['origem_id']??0); $destino=(int)($_POST['destino_id']??0);
            $trans=(int)($_POST['transportador_id']??0); $data=$_POST['data_frete']??date('Y-m-d');
            $produtos=$_POST['produtos']??[]; $quantidades=$_POST['quantidades']??[];
            if (!$origem || !$destino || !$trans || !$produtos) throw new Exception('Selecione origem, destino, transportador e pelo menos um produto.');
            if ($origem===$destino) throw new Exception('A origem e o destino devem ser diferentes.');
            $pdo->beginTransaction();
            $codigo='AMP-'.date('Ymd').'-'.strtoupper(substr(bin2hex(random_bytes(3)),0,6));
            $s=$pdo->prepare('INSERT INTO fretes(codigo,origem_id,destino_id,transportador_id,data_frete,observacoes) VALUES(?,?,?,?,?,?)');
            $s->execute([$codigo,$origem,$destino,$trans,$data,trim($_POST['observacoes']??'')]);
            $freteId=(int)$pdo->lastInsertId();
            $s=$pdo->prepare('INSERT INTO frete_produtos(frete_id,produto_id,quantidade) VALUES(?,?,?)');
            foreach ($produtos as $i=>$pid) { $q=max(1,(int)($quantidades[$i]??1)); $s->execute([$freteId,(int)$pid,$q]); }
            $pdo->commit();
            redirectTo('fretes','Frete '.$codigo.' criado com sucesso!');
        }
        if ($action === 'status') {
            $s=$pdo->prepare('UPDATE fretes SET status=? WHERE id=?');
            $s->execute([$_POST['status']??'Pendente',(int)($_POST['id']??0)]);
            redirectTo('fretes','Status atualizado!');
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error=$e->getMessage();
    }
}
if (isset($_GET['delete'],$_GET['type'])) {
    $map=['produto'=>'produtos','endereco'=>'enderecos','transportador'=>'transportadores'];
    $type=$_GET['type'];
    if (isset($map[$type])) {
        try { $s=$pdo->prepare("DELETE FROM {$map[$type]} WHERE id=?"); $s->execute([(int)$_GET['delete']]); redirectTo(['produto'=>'produtos','endereco'=>'enderecos','transportador'=>'transportadores'][$type],'Registro excluído.'); }
        catch(Throwable $e){ $error='Não foi possível excluir: este registro pode estar vinculado a um frete.'; }
    }
}

$edit=null;
if (isset($_GET['edit'])) {
    $tables=['produtos'=>'produtos','enderecos'=>'enderecos','transportadores'=>'transportadores'];
    if(isset($tables[$page])) { $s=$pdo->prepare("SELECT * FROM {$tables[$page]} WHERE id=?"); $s->execute([(int)$_GET['edit']]); $edit=$s->fetch(); }
}
$products=$pdo->query('SELECT * FROM produtos ORDER BY nome')->fetchAll();
$addresses=$pdo->query('SELECT * FROM enderecos ORDER BY identificacao')->fetchAll();
$carriers=$pdo->query('SELECT * FROM transportadores ORDER BY nome')->fetchAll();
$freights=$pdo->query("SELECT f.*, o.identificacao origem, d.identificacao destino, t.nome transportador
 FROM fretes f JOIN enderecos o ON o.id=f.origem_id JOIN enderecos d ON d.id=f.destino_id
 JOIN transportadores t ON t.id=f.transportador_id ORDER BY f.data_frete DESC,f.id DESC LIMIT 100")->fetchAll();
$today=countRows($pdo,'fretes');
?>
<!doctype html><html lang="pt-br"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>AMP FRETES | Gerenciador Logístico</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css"></head><body>
<div class="container-fluid"><div class="row">
<aside class="col-md-2 col-lg-2 sidebar p-3">
<a class="brand d-block mb-4" href="index.php">AMP <span class="text-info">FRETES</span></a>
<div class="small text-uppercase text-secondary mb-2">Menu principal</div>
<nav class="nav flex-column gap-1">
<a class="nav-link <?= $page==='dashboard'?'active':'' ?>" href="index.php">▦ Visão geral</a>
<a class="nav-link <?= $page==='produtos'?'active':'' ?>" href="?page=produtos">▣ Produtos</a>
<a class="nav-link <?= $page==='enderecos'?'active':'' ?>" href="?page=enderecos">⌖ Endereços</a>
<a class="nav-link <?= $page==='transportadores'?'active':'' ?>" href="?page=transportadores">▤ Transportadores</a>
<a class="nav-link <?= $page==='fretes'||$page==='novo_frete'?'active':'' ?>" href="?page=fretes">⇢ Fretes</a>
<a class="nav-link <?= $page==='relatorios'?'active':'' ?>" href="?page=relatorios">▧ Relatórios</a>
</nav><div class="mt-5 small text-secondary">Sistema acadêmico demonstrativo</div>
</aside>
<main class="col-md-10 col-lg-10 px-4">
<header class="topbar row align-items-center py-3 mb-4"><div class="col"><span class="fw-semibold">Gerenciador Logístico</span><div class="small text-secondary">Controle de produtos, rotas e transportes</div></div><div class="col-auto"><span class="badge text-bg-light">AMP FRETES</span></div></header>
<div class="content-wrap mx-auto">
<?php if(isset($_GET['msg'])): ?><div class="alert alert-success"><?=h($_GET['msg'])?></div><?php endif; ?>
<?php if(isset($error)): ?><div class="alert alert-danger"><?=h($error)?></div><?php endif; ?>

<?php if($page==='dashboard'): ?>
<h3 class="fw-bold mb-1">Visão geral</h3><p class="text-secondary">Acompanhe os principais dados da operação logística.</p>
<div class="row g-3 my-2">
<?php foreach([['Produtos',countRows($pdo,'produtos'),'▣'],['Endereços',countRows($pdo,'enderecos'),'⌖'],['Transportadores',countRows($pdo,'transportadores'),'▤'],['Fretes cadastrados',$today,'⇢']] as $st): ?>
<div class="col-sm-6 col-xl-3"><div class="card p-3"><div class="text-secondary"><?=$st[0]?></div><div class="d-flex justify-content-between align-items-center"><div class="stat-number"><?=$st[1]?></div><span class="fs-2 text-primary"><?=$st[2]?></span></div></div></div>
<?php endforeach; ?></div>
<div class="card p-4 mt-3"><div class="d-flex justify-content-between align-items-center mb-3"><h5 class="mb-0">Fretes recentes</h5><a class="btn btn-primary btn-sm" href="?page=novo_frete">+ Novo frete</a></div><?php renderFreights($freights); ?></div>
<?php elseif(in_array($page,['produtos','enderecos','transportadores'])): ?>
<?php
$cfg=[
'produtos'=>['Produtos','produto',['nome'=>'Nome do produto','descricao'=>'Descrição','peso'=>'Peso (kg)','valor'=>'Valor (R$)']],
'enderecos'=>['Endereços','endereco',['identificacao'=>'Identificação','logradouro'=>'Logradouro','numero'=>'Número','bairro'=>'Bairro','cidade'=>'Cidade','estado'=>'UF','cep'=>'CEP','complemento'=>'Complemento']],
'transportadores'=>['Transportadores','transportador',['nome'=>'Nome / Empresa','documento'=>'CPF/CNPJ','telefone'=>'Telefone','email'=>'E-mail','tipo_veiculo'=>'Tipo de veículo']]
][$page];
$rows=$pdo->query("SELECT * FROM {$page} ORDER BY id DESC")->fetchAll();
?>
<h3 class="fw-bold"><?=$cfg[0]?></h3><p class="text-secondary">Cadastre, consulte, edite e mantenha os registros do sistema.</p>
<div class="card p-4 mb-4"><h5><?= $edit?'Editar registro':'Novo cadastro' ?></h5>
<form method="post" class="row g-3"><input type="hidden" name="action" value="<?=$cfg[1]?>"><input type="hidden" name="id" value="<?=h($edit['id']??'')?>">
<?php foreach($cfg[2] as $field=>$label): ?><div class="<?=($field==='descricao'||$field==='complemento')?'col-12':'col-md-6'?>"><label class="form-label"><?=$label?></label>
<?php if($field==='descricao'||$field==='complemento'): ?><textarea class="form-control" name="<?=$field?>" rows="2"><?=h($edit[$field]??'')?></textarea>
<?php else: ?><input class="form-control" name="<?=$field?>" value="<?=h($edit[$field]??'')?>" <?=in_array($field,['nome','identificacao','logradouro','cidade','estado'])?'required':''?> <?=in_array($field,['peso','valor'])?'type="number" step="0.01" min="0"':''?>></div><?php endif; ?></div><?php endforeach; ?>
<div class="col-12"><button class="btn btn-primary">Salvar</button> <?php if($edit): ?><a class="btn btn-outline-secondary" href="?page=<?=$page?>">Cancelar</a><?php endif; ?></div></form></div>
<div class="card p-3"><div class="table-responsive"><table class="table align-middle"><thead><tr><?php foreach(array_keys($cfg[2]) as $f): ?><th><?=h($cfg[2][$f])?></th><?php endforeach; ?><th>Ações</th></tr></thead><tbody>
<?php foreach($rows as $r): ?><tr><?php foreach(array_keys($cfg[2]) as $f): ?><td><?=h($r[$f]??'')?></td><?php endforeach; ?><td class="text-nowrap"><a class="btn btn-sm btn-outline-primary" href="?page=<?=$page?>&edit=<?=$r['id']?>">Editar</a> <a class="btn btn-sm btn-outline-danger" onclick="return confirm('Excluir este registro?')" href="?page=<?=$page?>&delete=<?=$r['id']?>&type=<?=$cfg[1]?>">Excluir</a></td></tr><?php endforeach; ?>
<?php if(!$rows): ?><tr><td colspan="10" class="text-center text-secondary">Nenhum registro cadastrado.</td></tr><?php endif; ?></tbody></table></div></div>
<?php elseif($page==='novo_frete'): ?>
<h3 class="fw-bold">Gerar novo frete</h3><p class="text-secondary">Relacione transportador, origem, destino e um ou mais produtos.</p>
<?php if(!$products||count($addresses)<2||!$carriers): ?><div class="alert alert-warning">Antes de gerar um frete, cadastre produtos, pelo menos dois endereços e um transportador.</div><?php else: ?>
<form method="post" class="card p-4"><input type="hidden" name="action" value="frete"><div class="row g-3">
<div class="col-md-6"><label class="form-label">Endereço de origem</label><select class="form-select" name="origem_id" required><option value="">Selecione</option><?php foreach($addresses as $a): ?><option value="<?=$a['id']?>"><?=h($a['identificacao'].' — '.$a['cidade'].'/'.$a['estado'])?></option><?php endforeach; ?></select></div>
<div class="col-md-6"><label class="form-label">Endereço de destino</label><select class="form-select" name="destino_id" required><option value="">Selecione</option><?php foreach($addresses as $a): ?><option value="<?=$a['id']?>"><?=h($a['identificacao'].' — '.$a['cidade'].'/'.$a['estado'])?></option><?php endforeach; ?></select></div>
<div class="col-md-6"><label class="form-label">Transportador</label><select class="form-select" name="transportador_id" required><option value="">Selecione</option><?php foreach($carriers as $c): ?><option value="<?=$c['id']?>"><?=h($c['nome'])?></option><?php endforeach; ?></select></div>
<div class="col-md-6"><label class="form-label">Data do frete</label><input class="form-control" type="date" name="data_frete" value="<?=date('Y-m-d')?>" required></div>
<div class="col-12"><label class="form-label">Produtos transportados</label><div id="product-list"><div class="row g-2 mb-2 product-row"><div class="col-md-8"><select class="form-select" name="produtos[]" required><option value="">Selecione um produto</option><?php foreach($products as $p): ?><option value="<?=$p['id']?>"><?=h($p['nome'])?></option><?php endforeach; ?></select></div><div class="col-md-3"><input class="form-control" type="number" min="1" name="quantidades[]" value="1" required></div><div class="col-md-1"><button type="button" class="btn btn-outline-danger" onclick="this.closest('.product-row').remove()">×</button></div></div></div>
<button type="button" class="btn btn-outline-primary btn-sm" id="add-product">+ Adicionar outro produto</button></div>
<div class="col-12"><label class="form-label">Observações</label><textarea class="form-control" name="observacoes" rows="3"></textarea></div>
<div class="col-12"><button class="btn btn-primary">Gerar frete</button> <a class="btn btn-outline-secondary" href="?page=fretes">Cancelar</a></div></div></form>
<?php endif; ?>
<?php elseif($page==='fretes'): ?>
<div class="d-flex justify-content-between align-items-center"><div><h3 class="fw-bold">Fretes</h3><p class="text-secondary">Consulta e acompanhamento dos transportes.</p></div><a class="btn btn-primary" href="?page=novo_frete">+ Gerar frete</a></div>
<div class="card p-3"><?php renderFreights($freights,true); ?></div>
<?php elseif($page==='relatorios'): ?>
<h3 class="fw-bold">Relatórios de fretes</h3><p class="text-secondary">Selecione um dia para exportar os fretes cadastrados em formato CSV, compatível com Excel.</p>
<div class="card p-4"><form method="get" class="row g-3 align-items-end"><input type="hidden" name="page" value="relatorios"><div class="col-md-4"><label class="form-label">Data</label><input class="form-control" type="date" name="data" value="<?=h($_GET['data']??date('Y-m-d'))?>" required></div><div class="col-auto"><button class="btn btn-primary">Consultar</button></div><div class="col-auto"><a class="btn btn-outline-success" href="relatorio.php?data=<?=h($_GET['data']??date('Y-m-d'))?>">Baixar CSV</a></div></form>
<?php if(isset($_GET['data'])): $s=$pdo->prepare("SELECT f.codigo,f.data_frete,f.status,t.nome transportador,o.identificacao origem,d.identificacao destino,GROUP_CONCAT(CONCAT(p.nome,' (',fp.quantidade,')') SEPARATOR ', ') produtos FROM fretes f JOIN transportadores t ON t.id=f.transportador_id JOIN enderecos o ON o.id=f.origem_id JOIN enderecos d ON d.id=f.destino_id LEFT JOIN frete_produtos fp ON fp.frete_id=f.id LEFT JOIN produtos p ON p.id=fp.produto_id WHERE f.data_frete=? GROUP BY f.id ORDER BY f.codigo"); $s->execute([$_GET['data']]); $list=$s->fetchAll(); ?><hr><h6>Resultado: <?=count($list)?> frete(s)</h6><div class="table-responsive"><table class="table"><thead><tr><th>Código</th><th>Transportador</th><th>Origem</th><th>Destino</th><th>Produtos</th><th>Status</th></tr></thead><tbody><?php foreach($list as $r): ?><tr><td><?=h($r['codigo'])?></td><td><?=h($r['transportador'])?></td><td><?=h($r['origem'])?></td><td><?=h($r['destino'])?></td><td><?=h($r['produtos'])?></td><td><?=h($r['status'])?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></div>
<?php endif; ?>
<footer class="text-center py-4 mt-4">AMP FRETES · Projeto acadêmico · Fundamentos de Programação para Internet</footer>
</div></main></div></div>
<script>
document.getElementById('add-product')?.addEventListener('click',()=>{
 const first=document.querySelector('.product-row'); if(!first)return;
 const clone=first.cloneNode(true); clone.querySelector('select').value=''; clone.querySelector('input').value='1';
 document.getElementById('product-list').appendChild(clone);
});
</script></body></html>
<?php
function renderFreights(array $rows, bool $withStatus=false): void { ?>
<div class="table-responsive"><table class="table align-middle"><thead><tr><th>Código</th><th>Data</th><th>Origem</th><th>Destino</th><th>Transportador</th><th>Status</th><?php if($withStatus): ?><th>Atualizar</th><?php endif; ?></tr></thead><tbody>
<?php foreach($rows as $r): ?><tr><td class="fw-semibold"><?=h($r['codigo'])?></td><td><?=date('d/m/Y',strtotime($r['data_frete']))?></td><td><?=h($r['origem'])?></td><td><?=h($r['destino'])?></td><td><?=h($r['transportador'])?></td><td><span class="badge text-bg-light"><?=h($r['status'])?></span></td>
<?php if($withStatus): ?><td><form method="post" class="d-flex gap-1"><input type="hidden" name="action" value="status"><input type="hidden" name="id" value="<?=$r['id']?>"><select name="status" class="form-select form-select-sm"><?php foreach(['Pendente','Em transporte','Entregue','Cancelado'] as $st): ?><option <?=$r['status']===$st?'selected':''?>><?=$st?></option><?php endforeach; ?></select><button class="btn btn-sm btn-outline-primary">OK</button></form></td><?php endif; ?></tr><?php endforeach; ?>
<?php if(!$rows): ?><tr><td colspan="8" class="text-center text-secondary py-4">Nenhum frete cadastrado.</td></tr><?php endif; ?></tbody></table></div><?php } ?>
