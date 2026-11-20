# Etapa 1 — Análise de telas | AMP FRETES

## Objetivo
Desenvolver uma aplicação web para organizar produtos, endereços, transportadores e fretes, permitindo que um transportador leve vários produtos em uma mesma operação.

## Mapa de navegação
- Visão geral
- Produtos
- Endereços
- Transportadores
- Fretes
  - Consultar fretes
  - Gerar novo frete
- Relatórios

## Telas previstas
1. **Visão geral:** cartões com totais de produtos, endereços, transportadores e fretes; tabela de fretes recentes.
2. **Produtos:** formulário para nome, descrição, peso e valor; listagem com edição e exclusão.
3. **Endereços:** identificação, logradouro, número, bairro, cidade, UF, CEP e complemento. O mesmo cadastro pode ser selecionado como origem ou destino.
4. **Transportadores:** nome/empresa, CPF/CNPJ, telefone, e-mail e tipo de veículo.
5. **Gerar frete:** seleção de origem, destino, transportador, data e múltiplos produtos com quantidades.
6. **Fretes:** código gerado automaticamente, rota, transportador e status; opção para atualizar o status.
7. **Relatórios:** filtro por dia e exportação CSV.

## Regras de negócio
- Um frete deve possuir origem, destino, transportador e pelo menos um produto.
- Origem e destino não podem ser o mesmo endereço.
- Um frete pode possuir vários produtos, registrados na tabela de associação `frete_produtos`.
- Cada frete recebe um código identificador automático.
- O relatório deve listar os fretes de uma data escolhida.

## Identidade visual
Interface responsiva, com menu lateral azul-marinho, área de conteúdo clara, cartões de indicadores, formulários objetivos e tabelas. Bootstrap é utilizado para organizar os componentes e adaptar as telas a computadores e dispositivos móveis.
