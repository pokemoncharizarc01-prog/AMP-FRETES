# AMP FRETES — Gerenciador Logístico

Projeto acadêmico desenvolvido para a APO da disciplina **Fundamentos de Programação para Internet**.

## Funcionalidades
- Painel com resumo de produtos, endereços, transportadores e fretes.
- Cadastro, edição e exclusão de produtos.
- Cadastro, edição e exclusão de endereços de origem e destino.
- Cadastro, edição e exclusão de transportadores.
- Geração de frete relacionando origem, destino, transportador e vários produtos.
- Atualização do status do frete.
- Relatório diário exportável em CSV (abre no Excel).
- Interface responsiva com Bootstrap e estilos próprios.

## Tecnologias
PHP 8+, MySQL/MariaDB, PDO, HTML5, CSS3, JavaScript e Bootstrap 5.

## Instalação no XAMPP (Windows)
1. Extraia a pasta `AMP_FRETES` para `C:\xampp\htdocs\`.
2. Abra o painel do XAMPP e inicie **Apache** e **MySQL**.
3. Acesse `http://localhost/phpmyadmin`.
4. Importe o arquivo `database/amp_fretes.sql` (aba **Importar**).
5. Se seu MySQL usar usuário/senha diferentes, ajuste `config/conexao.php`.
6. Acesse `http://localhost/AMP_FRETES/`.

## Fluxo sugerido para testar
1. Abra Produtos e cadastre um produto.
2. Em Endereços, cadastre os locais de origem e destino.
3. Em Transportadores, cadastre o responsável pelo transporte.
4. Abra Fretes > Gerar frete; selecione os dois endereços, um transportador e um ou mais produtos.
5. Em Fretes, altere o status para “Em transporte” ou “Entregue”.
6. Em Relatórios, escolha a data e baixe o CSV.

## Estrutura
- `index.php`: páginas, formulários, consultas e operações do sistema.
- `relatorio.php`: exportação do relatório diário.
- `config/conexao.php`: conexão PDO com o banco.
- `database/amp_fretes.sql`: criação do banco, tabelas e registros demonstrativos.
- `assets/style.css`: identidade visual.
- `docs/analise-telas.pdf`: planejamento visual da Etapa 1.

## Modelo de dados
`fretes` referencia um endereço de origem, um endereço de destino e um transportador. A tabela `frete_produtos` permite associar vários produtos a um mesmo frete (relação um-para-muitos entre frete e itens).

## Git e entrega
Na pasta do projeto, execute:
```bash
git init
git add .
git commit -m "Versao inicial do AMP FRETES"
```
Crie um repositório **público** no GitHub e siga as instruções de envio (`git remote add origin ...` e `git push -u origin main`). O link público do GitHub precisa ser criado na sua conta; este pacote ainda não está publicado em um repositório remoto.
