# 🎬 Reel Marketplace - Explorar Feed

![Plugin Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![WordPress](https://img.shields.io/badge/WordPress-5.0+-green.svg)
![WooCommerce](https://img.shields.io/badge/WooCommerce-4.0+-orange.svg)
![PHP](https://img.shields.io/badge/PHP-7.4+-purple.svg)

**Plugin WordPress para marketplace multi-vendedor com feed de vídeos curtos verticais estilo Instagram Reels/TikTok totalmente integrado ao e-commerce.**

## 📋 Índice

- [Visão Geral](#-visão-geral)
- [Funcionalidades](#-funcionalidades)
- [Requisitos](#-requisitos)
- [Instalação](#-instalação)
- [Configuração](#-configuração)
- [Como Usar](#-como-usar)
- [Integrações](#-integrações)
- [Personalização](#-personalização)
- [Troubleshooting](#-troubleshooting)
- [Suporte](#-suporte)

## 🌟 Visão Geral

O **Reel Marketplace** transforma seu marketplace multi-vendedor em uma experiência moderna e envolvente, similar ao Instagram Reels ou TikTok, onde vendedores podem criar vídeos curtos verticais para promover seus produtos de forma direta e interativa.

### 🎯 Principais Benefícios

- **📱 Experiência Mobile-First**: Interface otimizada para dispositivos móveis
- **🛒 Conversão Direta**: Compra de produtos direto do vídeo
- **👥 Multi-Vendedor**: Integração completa com Dokan e WCFM
- **📊 Analytics Avançados**: Métricas detalhadas de engajamento e vendas
- **🎨 Design Moderno**: Interface Material Design 3 responsiva

## ✨ Funcionalidades

### 🎬 Feed de Vídeos
- **Formato Vertical 9:16**: Otimizado para mobile
- **Autoplay Inteligente**: Reprodução automática com controle de volume
- **Navegação Suave**: Swipe vertical infinito
- **Preload**: Carregamento antecipado dos próximos vídeos

### 🛍️ E-commerce Integrado
- **Produtos Vinculados**: Associação direta produto-vídeo
- **Carrinho Rápido**: Adicionar ao carrinho sem sair do vídeo
- **Variações de Produto**: Modal para seleção de variações
- **Checkout Express**: Botão "Comprar Agora" integrado

### ❤️ Interações Sociais
- **Sistema de Curtidas**: Engajamento em tempo real
- **Favoritos/Wishlist**: Lista de desejos integrada
- **Compartilhamento**: WhatsApp, Instagram, Facebook, Twitter, email
- **Comentários**: Sistema de comentários (futuro)

### 📊 Analytics & Métricas
- **Visualizações**: Tracking automático de views
- **Engajamento**: Métricas de curtidas e compartilhamentos
- **Conversões**: Rastreamento de vendas por reel
- **Dashboard**: Painel completo para administradores e vendedores

### 🎨 Design & UX
- **Material Design 3**: Interface moderna e expressiva
- **Cores Dinâmicas**: Sistema de cores adaptativo
- **Animações Fluidas**: Transições suaves e naturais
- **Acessibilidade**: Compatível com leitores de tela

## 📋 Requisitos

### Requisitos Mínimos
- **WordPress**: 5.0 ou superior
- **PHP**: 7.4 ou superior
- **WooCommerce**: 4.0 ou superior
- **MySQL**: 5.6 ou superior

### Requisitos Recomendados
- **WordPress**: 6.4 ou superior
- **PHP**: 8.1 ou superior
- **WooCommerce**: 8.0 ou superior
- **MySQL**: 8.0 ou superior
- **FFmpeg**: Para processamento de vídeo (opcional)

### Plugins Compatíveis
- **Dokan**: 3.0 ou superior
- **WCFM**: 6.0 ou superior
- **WooCommerce Product Variations**: Nativo

### Tecnologias Utilizadas
- **Frontend**: HTML5, CSS3, JavaScript (ES6+), Material Design 3
- **Backend**: PHP 7.4+, WordPress APIs, WooCommerce APIs
- **Database**: MySQL com tabelas customizadas
- **Video**: FFmpeg para processamento (opcional)

## 🚀 Instalação

### Método 1: Instalação Manual

1. **Download do Plugin**
   ```bash
   # Clone o repositório ou baixe o arquivo ZIP
   git clone https://github.com/your-repo/reel-marketplace.git
   ```

2. **Upload para WordPress**
   - Acesse seu servidor via FTP/cPanel
   - Navegue até `/wp-content/plugins/`
   - Faça upload da pasta `reel-marketplace`

3. **Ativação**
   - Acesse `Administração > Plugins`
   - Localize "Reel Marketplace - Explorar Feed"
   - Clique em "Ativar"

### Método 2: Upload via WordPress Admin

1. **Preparar Arquivo ZIP**
   - Compacte a pasta `reel-marketplace` em um arquivo ZIP

2. **Upload via Admin**
   - Acesse `Administração > Plugins > Adicionar Novo`
   - Clique em "Fazer Upload do Plugin"
   - Selecione o arquivo ZIP
   - Clique em "Instalar Agora"
   - Clique em "Ativar Plugin"

### 🔧 Verificação de Instalação

Após a ativação, verifique se:
- [ ] Apareceu o menu "Reels" no admin do WordPress
- [ ] Não há erros na página de plugins
- [ ] O custom post type "reel" foi criado
- [ ] As tabelas de database foram criadas

## ⚙️ Configuração

### 1. Configurações Básicas

Acesse `Administração > Reels > Configurações`:

```
✅ Autoplay de Vídeos: Habilitado
📊 Reels por Página: 10
🔍 Moderação: Aprovação automática
📹 Tamanho Máximo: 100MB
⏱️ Duração Máxima: 60 segundos
🎬 Processamento: Ativado
📈 Analytics: Habilitado
```

### 2. Configurações de Vídeo

**Formatos Aceitos:**
- MP4 (recomendado)
- MOV
- AVI
- WebM

**Especificações Recomendadas:**
- **Resolução**: 1080x1920 (9:16)
- **Taxa de Bits**: 8-12 Mbps
- **Frame Rate**: 30 FPS
- **Duração**: 15-60 segundos

### 3. Configuração do FFmpeg (Opcional)

Para processamento avançado de vídeo:

```bash
# Ubuntu/Debian
sudo apt update
sudo apt install ffmpeg

# CentOS/RHEL
sudo yum install ffmpeg

# macOS
brew install ffmpeg
```

No WordPress, adicione ao `wp-config.php`:
```php
define('FFMPEG_PATH', '/usr/bin/ffmpeg');
```

### 4. Configuração de Marketplace

#### Para Dokan:
1. Ative o plugin Dokan
2. Configure as permissões de vendedor
3. O menu "Reels" aparecerá automaticamente no dashboard

#### Para WCFM:
1. Ative o plugin WCFM
2. Configure as capacidades dos vendedores
3. O menu "Reels" será adicionado ao WCFM

## 📖 Como Usar

### 👤 Para Administradores

#### Dashboard Principal
Acesse `Administração > Reels > Dashboard`:

- **📊 Estatísticas**: Visão geral de métricas
- **📈 Analytics**: Gráficos detalhados
- **🔍 Moderação**: Aprovação de conteúdo
- **⚙️ Configurações**: Ajustes do sistema

#### Gerenciar Reels
Acesse `Administração > Reels > Todos os Reels`:

- **📋 Lista Completa**: Todos os reels do site
- **🔍 Filtros**: Por autor, status, data
- **✏️ Edição**: Editar qualquer reel
- **🗑️ Exclusão**: Remover reels inadequados

### 🏪 Para Vendedores (Dokan)

#### Acessar Dashboard
1. Faça login na sua conta de vendedor
2. Acesse "Meu Dashboard"
3. Clique em "Reels" no menu lateral

#### Criar Novo Reel
1. **Dashboard Dokan > Reels > Novo Reel**
2. **Preencha os dados:**
   ```
   📝 Título: Nome do seu reel
   📄 Descrição: Conte sobre o produto
   🎬 Vídeo: Upload do arquivo (max 100MB)
   🛍️ Produto: Selecione produtos para vincular
   🏷️ Tags: Adicione palavras-chave
   ```
3. **Clique em "Criar Reel"**

#### Gerenciar Reels
- **📊 Estatísticas**: Visualizações, curtidas, conversões
- **📈 Analytics**: Gráficos de performance
- **✏️ Editar**: Modificar reels existentes
- **🗑️ Deletar**: Remover reels

### 🏪 Para Vendedores (WCFM)

#### Acessar Dashboard
1. Faça login na sua conta de vendedor
2. Acesse o WCFM Dashboard
3. Clique em "Reels" no menu

#### Processo Similar ao Dokan
- Interface integrada ao WCFM
- Funcionalidades idênticas
- Analytics no dashboard WCFM

### 👥 Para Usuários/Compradores

#### Visualizar Feed
1. **Acesse a página com o shortcode `[reel_feed]`**
2. **Navegação:**
   - 📱 Swipe vertical para próximo/anterior
   - ⌨️ Setas do teclado (↑↓)
   - 🖱️ Botões de navegação

#### Interagir com Reels
- **❤️ Curtir**: Clique no coração
- **🔗 Compartilhar**: Clique no ícone de share
- **🛒 Comprar**: Clique no produto ou botão de compra
- **⭐ Favoritar**: Adicione à wishlist

### 📄 Implementação no Site

#### Shortcode Básico
```php
[reel_feed]
```

#### Shortcode com Parâmetros
```php
[reel_feed per_page="15" category="moda" featured="true"]
```

#### Template Personalizado
```php
<?php echo do_shortcode('[reel_feed]'); ?>
```

#### Página Dedicada
Crie uma nova página e adicione:
```
Título: Explorar
Conteúdo: [reel_feed]
Template: Página Inteira (sem sidebar)
```

## 🔗 Integrações

### 🛒 WooCommerce
- **Produtos**: Vinculação automática
- **Carrinho**: Integração nativa
- **Checkout**: Processo simplificado
- **Variações**: Suporte completo

### 🏪 Dokan
- **Dashboard**: Menu integrado
- **Permissões**: Controle por vendedor
- **Analytics**: Métricas individuais
- **Subscriptions**: Compatível com Dokan Pro

### 🏪 WCFM
- **Interface**: Menu nativo WCFM
- **Capabilities**: Sistema de permissões
- **Marketplace**: Multi-vendedor completo
- **Analytics**: Dashboard integrado

### 📱 Redes Sociais
- **WhatsApp**: Compartilhamento direto
- **Instagram**: Stories e posts
- **Facebook**: Timeline e grupos
- **Twitter**: Tweets com vídeo
- **Email**: Compartilhamento por email

## 🎨 Personalização

### 🎨 Customização de Cores

Adicione ao seu tema `functions.php`:
```php
add_filter('reel_marketplace_colors', function($colors) {
    return array(
        'primary' => '#6750A4',
        'secondary' => '#7D5260',
        'tertiary' => '#7D5260',
        'surface' => '#FFFBFE',
        'background' => '#FFFBFE'
    );
});
```

### 🎬 Custom Templates

Copie os templates para seu tema:
```
/wp-content/themes/seu-tema/
├── reel-marketplace/
│   ├── single-reel.php
│   ├── archive-reel.php
│   └── reel-feed.php
```

### 📱 CSS Personalizado

```css
/* Personalizar cores */
:root {
    --reel-primary: #your-color;
    --reel-secondary: #your-color;
}

/* Personalizar layout */
.reel-feed-container {
    /* Seus estilos */
}
```

### 🔧 Hooks Disponíveis

#### Actions
```php
// Antes de renderizar o feed
do_action('reel_marketplace_before_feed');

// Após renderizar item
do_action('reel_marketplace_after_item', $reel_id);

// Após upload de vídeo
do_action('reel_marketplace_video_uploaded', $file_path, $vendor_id);
```

#### Filters
```php
// Modificar argumentos do feed
add_filter('reel_marketplace_feed_args', $args);

// Personalizar item do reel
add_filter('reel_marketplace_item_html', $html, $reel_id);

// Controle de permissões
add_filter('reel_marketplace_can_create_reel', $can_create, $user_id);
```

## 📊 Analytics e Métricas

### 📈 Métricas Disponíveis
- **👀 Visualizações**: Total e por período
- **❤️ Curtidas**: Engajamento dos usuários
- **🔗 Compartilhamentos**: Alcance viral
- **🛒 Conversões**: Vendas geradas
- **⏱️ Tempo de Visualização**: Retenção de audiência

### 📊 Dashboard Analytics
- **Gráficos Interativos**: Chart.js integrado
- **Filtros por Período**: 7, 30, 90 dias, 1 ano
- **Exportação**: CSV e PDF
- **Comparação**: Períodos anteriores

### 🎯 Funil de Conversão
1. **Impressão**: Reel visualizado
2. **Engajamento**: Curtida ou compartilhamento
3. **Interesse**: Clique no produto
4. **Conversão**: Compra realizada

## 🔧 Troubleshooting

### ❌ Problemas Comuns

#### Vídeos Não Carregam
```
Possíveis Causas:
✅ Verificar formato do arquivo (MP4 recomendado)
✅ Confirmar tamanho máximo (100MB padrão)
✅ Testar conexão de internet
✅ Verificar permissões de arquivo
```

#### Feed Não Aparece
```
Soluções:
✅ Verificar se o shortcode está correto
✅ Confirmar se há reels publicados
✅ Testar conflitos com outros plugins
✅ Verificar JavaScript no console
```

#### Erro de Permissões
```
Verificações:
✅ Plugin WooCommerce ativo
✅ Usuário é vendedor (Dokan/WCFM)
✅ Capacidades de vendedor configuradas
✅ Status da conta de vendedor
```

#### Performance Lenta
```
Otimizações:
✅ Ativar cache de página
✅ Otimizar imagens e vídeos
✅ Configurar CDN
✅ Usar FFmpeg para compressão
```

### 🔍 Debug Mode

Ative o debug no `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('REEL_MARKETPLACE_DEBUG', true);
```

### 📝 Logs
Verifique os logs em:
- `/wp-content/debug.log`
- `/wp-content/uploads/reel-logs/`

## 🚀 Performance e Otimização

### 📹 Otimização de Vídeo

#### FFmpeg Settings
```bash
# Compressão automática
ffmpeg -i input.mp4 -c:v libx264 -preset slow -crf 23 -c:a aac -b:a 128k output.mp4

# Thumbnail generation
ffmpeg -i input.mp4 -ss 00:00:01 -vframes 1 thumbnail.jpg
```

#### Configurações Recomendadas
```php
// wp-config.php
define('REEL_VIDEO_QUALITY', 'high'); // low, medium, high
define('REEL_GENERATE_THUMBNAILS', true);
define('REEL_AUTO_COMPRESS', true);
```

### 🚀 Cache e Performance

#### Recomendações
- **Plugin de Cache**: WP Rocket, W3 Total Cache
- **CDN**: CloudFlare, Amazon CloudFront
- **Otimização**: WP Optimize, Autoptimize
- **Imagens**: WebP, lazy loading

### 📱 Mobile Optimization

- **Viewport**: Responsivo automático
- **Touch**: Navegação por gestos
- **Performance**: Lazy loading de vídeos
- **Offline**: Service Worker (futuro)

## 🔐 Segurança

### 🛡️ Validações Implementadas
- **Nonces**: Todas as requisições AJAX
- **Sanitização**: Inputs de usuário
- **Validação**: Tipos de arquivo
- **Permissões**: Controle de acesso

### 🔒 Recomendações de Segurança
- **SSL**: Certificado obrigatório
- **Firewall**: Plugin de segurança
- **Backups**: Rotina automática
- **Updates**: Manter plugins atualizados

## 📱 Roadmap

### 🚧 Próximas Versões

#### v1.1.0 (Q2 2025)
- [ ] Sistema de comentários
- [ ] Stories temporárias (24h)
- [ ] Live streaming
- [ ] Push notifications

#### v1.2.0 (Q3 2025)
- [ ] IA para recomendações
- [ ] Editor de vídeo integrado
- [ ] Filtros e efeitos
- [ ] Multi-idiomas

#### v2.0.0 (Q4 2025)
- [ ] App mobile nativo
- [ ] Realidade aumentada (AR)
- [ ] Blockchain/NFTs
- [ ] Integrações avançadas

## 🤝 Contribuição

### 🔧 Como Contribuir
1. **Fork** o repositório
2. **Clone** sua fork
3. **Crie** uma branch para sua feature
4. **Implemente** suas mudanças
5. **Teste** completamente
6. **Submeta** um Pull Request

### 📋 Guidelines
- **Código**: Seguir WordPress Coding Standards
- **Documentação**: Comentar funções complexas
- **Testes**: Incluir testes unitários
- **Compatibilidade**: Manter retrocompatibilidade

## 📞 Suporte

### 🆘 Canais de Suporte
- **📧 Email**: suporte@reelmarketplace.com
- **💬 Discord**: [Servidor da Comunidade]
- **📱 WhatsApp**: +55 (11) 99999-9999
- **🐛 Issues**: [GitHub Issues]

### 📚 Documentação
- **🌐 Wiki**: [Documentação Completa]
- **🎥 Tutoriais**: [Canal YouTube]
- **📖 Blog**: [Artigos e Dicas]

### 💰 Suporte Premium
- **🚀 Instalação**: Configuração completa
- **🎨 Customização**: Design personalizado
- **🔧 Desenvolvimento**: Features exclusivas
- **📞 Suporte 24/7**: Atendimento prioritário

---

## 📄 Licença

Este plugin é licenciado sob a **GPL v2 ou posterior**.

```
Copyright (C) 2025 Reel Marketplace

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```

---

## 🙏 Agradecimentos

- **WordPress Community**: Pela plataforma incrível
- **WooCommerce Team**: Pela base de e-commerce
- **Dokan & WCFM**: Pelas integrações de marketplace
- **Material Design**: Pelo sistema de design
- **Contributors**: Todos que ajudaram no desenvolvimento

---

**🎬 Transforme seu marketplace em uma experiência viral com Reel Marketplace!**

*Desenvolvido com ❤️ para a comunidade WordPress*
