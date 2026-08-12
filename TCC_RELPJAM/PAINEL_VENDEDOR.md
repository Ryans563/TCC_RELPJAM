# Painel de vendedor — RELPJAM

Implementado:

- Login de usuário com `tipo_usuario = vendedor` redirecionando para `app/views/vendedor.php`.
- Link **Minha Loja** no perfil do marketplace para vendedores.
- Loja individual vinculada ao usuário pela tabela `vendedores.usuario_id`.
- Dashboard com produtos, estoque, vendas, faturamento e pedidos.
- Cadastro e edição de produtos.
- Até 10 imagens por produto.
- Definição automática da primeira imagem como principal.
- Troca da imagem principal e remoção de imagens na edição.
- Categorias próprias do vendedor + categorias globais da plataforma.
- Configuração de nome, descrição, logo e banner da loja.
- Controle de status do produto: ativo, pausado e inativo.
- Proteção CSRF e verificações de propriedade para impedir edição de produtos de outro vendedor.
- Cadastro de vendedor funcional em `app/views/vendedorcad.php`.

## Banco de dados

Se o banco já existe, execute **uma única vez**:

`seller_dashboard_migration.sql`

Se estiver instalando do zero, use o `marketplace_def.sql` atualizado.

## Fluxo

`sign.php` → verifica `usuarios.tipo_usuario` → `vendedor` → `vendedor.php`

O vendedor trabalha somente com os registros cujo `vendedor_id` corresponde à própria conta.
