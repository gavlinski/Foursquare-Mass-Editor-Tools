Foursquare Mass Editor Tools (Elio Tools)
=========================================

This is a collection of Superuser Tools for Foursquare venues mass/bulk editing and searching powered by Foursquare API.

It was built using HTML, CSS and JavaScript with Dojo Toolkit on client-side, and also PHP for some legacy features on server-side. Future implementations may be rebuilt from scratch using only JavaScript in a single-page application (SPA).

Key Features
------------

* Import full data from a UTF-8 CSV file
* Import venues from:
  * Plain text files
  * Public web page addresses
  * Manually entered IDs or URLs 
  * IDs passed as parameters to `load.php?venues=`
* Search venues using Foursquare API
* Almost all fields editing
* All types of flagging
* Export options:
  * Full CSV file (UTF-8)
  * Venues URLs only
  * Editing and flagging Report
* Hierarchical tree viewing of categories
* Categories editing
* Multiple fields editing
* Google Maps locations markers

Requirements for (Super)Use
---------------------------

1. To be a Foursquare user or superuser (preferably)
2. Go to http://4sq.eliotools.site
3. Allow application access to your account

If you are not familiar with Brazilian Portuguese language, please translate it using you favorite browser tool. English users report that it works well and is easily understood.

Internet Explorer obviously not supported. Please use Mozilla Firefox, Google Chrome or Apple Safari.

**User abuses will not be tolerated, so please be warned.**

Requirements for development
----------------------------

* PHP 8.1+
* [Composer](https://getcomposer.org/)
* Docker (opcional)

## Quick Start

### Usando Docker (Recomendado)

1. Clone o repositório:
   ```bash
   git clone https://github.com/gavlinski/Foursquare-Mass-Editor-Tools.git
   cd Foursquare-Mass-Editor-Tools
   ```

2. Configure as variáveis de ambiente:
   ```bash
   cp .env.example .env
   # Edite o arquivo .env com suas credenciais do Foursquare
   ```

3. Execute com Docker:
   ```bash
   chmod +x dev.sh
   ./dev.sh run
   ```

4. Acesse: http://localhost/4sqmet

### Instalação Manual

1. Instale as dependências:
   ```bash
   composer install
   ```

2. Configure o servidor web para apontar para a pasta do projeto

3. Configure as variáveis de ambiente no arquivo `.env`

Requirements for development
----------------------------

* PHP 8.1+
* [Composer](https://getcomposer.org/)
* Docker (opcional)

Contributions are always welcome. :)

License
-------

This project is licensed under the terms of the [GNU General Public License v3.0](LICENSE.txt).

Thanks for reading!
