<?php 

/**
 * Controlador para generar modelos y controladores de forma dinámica
 */
class creatorController extends Controller {
  function __construct()
  {
    // Prevenir el ingreso en Producción
    if (!is_local()) {
      Redirect::to(DEFAULT_CONTROLLER);
    }
  }
  
  function index() {
    View::render('index', ['title' => 'Crea un nuevo archivo']);
  }

  function controller()
  {
    View::render('controller', ['title' => 'Nuevo controlador']);
  }

  function model()
  {
    View::render('model', ['title' => 'Nuevo modelo']);
  }

  function post_controller()
  {
    if (!Csrf::validate($_POST['csrf'])) {
      Flasher::deny();
      Redirect::back();
    }

    // Validar nombre de archivo
    $name     = clean($_POST['filename']);
    $filename = strtolower($name);
    $filename = str_replace(' ', '_', $filename);
    $filename = str_replace('.php', '', $filename);
    $keyword  = 'Controller';
    $g_vista  = isset($_POST["generar-vista"]) ? true : false;
    $template = MODULES . 'bee' . DS . 'controllerTemplate.txt';

    // Validar que sea un string válido
    if (!is_string($name)) {
      Flasher::error(sprintf('Ingresa un nombre de controlador válido por favor.', $name));
      Redirect::back();
    }

    // Validar longitud del nombre
    if (strlen($name) < 5) {
      Flasher::error(sprintf('Ingresa un nombre de controlador válido por favor, <b>%s</b> es demasiado corto.', $name));
      Redirect::back();
    }

    // Validar la existencia del controlador para prevenir remover un archivo existente
    if (is_file(CONTROLLERS.$filename.$keyword.'.php')) {
      Flasher::new(sprintf('Ya existe el controlador %s.', $filename.$keyword), 'danger');
      Redirect::back();
    }

    // Validar la existencia de la plantilla.txt para crear el controlador
    if (!is_file($template)) {
      Flasher::new(sprintf('No existe la plantilla %s.', $template), 'danger');
      Redirect::back();
    }
    
    // Cargar contenido del archivo
    $php = @file_get_contents($template);
    $php = str_replace('[[REPLACE]]', $filename, $php);

    // Generar el archivo del controlador
    if (file_put_contents(CONTROLLERS . $filename . $keyword . '.php', $php) === false)  {
      Flasher::new(sprintf('Ocurrió un problema al crear el controlador %s.', $template), 'danger');
      Redirect::back();
    }

    // Crear el folder en carpeta vistas solo si es requerido
    if (!is_dir(VIEWS . $filename) && $g_vista === true) {
      mkdir(VIEWS . $filename);
    }

    // Generar la vista solo si así se solicita
    if ($g_vista === true) {
      $html_template = MODULES . 'bee' . DS . 'viewTemplate.txt';

      if (is_file($html_template)) {
        $html = @file_get_contents($html_template);
      } else {
        $html =
        '<?php require_once INCLUDES.\'inc_header.php\'; ?>
        <div class="container">
          <div class="row">
            <div class="col-6 text-center offset-xl-3">
              <a href="<?php echo URL; ?>"><img src="<?php echo IMAGES.\'bee_logo.png\' ?>" alt="Bee framework" class="img-fluid" style="width: 200px;"></a>
              <h2 class="mt-5 mb-3"><span class="text-warning">Bee</span> framework</h2>
              <!-- contenido -->
              <h1><?php echo $d->msg; ?></h1>
              <!-- ends -->
            </div>
          </div>
        </div>
        <?php require_once INCLUDES.\'inc_bee_footer.php\'; ?>';
      }

      @file_put_contents(VIEWS . $filename . DS . 'indexView.php', $html);
    }

    // Crear una vista por defecto
    Redirect::to($filename);
  }

  function post_model()
  {
    if (!Csrf::validate($_POST['csrf'])) {
      Flasher::deny();
      Redirect::back();
    }

    // Validar nombre de archivo
    $name     = clean($_POST["filename"]);
    $table    = clean($_POST["tabla"]);
    $scheme   = clean($_POST["esquema"]);
    $scheme_s = '';
    $filename = strtolower($name);
    $filename = str_replace(' ', '_', $filename);
    $filename = str_replace('.php', '', $filename);
    $keyword  = 'Model';
    $template = MODULES . 'bee' . DS . 'modelTemplate.txt';

    // Validar longitud del nombre
    if (strlen($name) < 4) {
      Flasher::error(sprintf('Ingresa un nombre de modelo válido por favor, <b>%s</b> es demasiado corto.', $name));
      Redirect::back();
    }

    // Validar longitud del nombre
    if (strlen($table) < 4 && !empty($table)) {
      Flasher::error(sprintf('Ingresa un nombre de tabla válido por favor, <b>%s</b> es demasiado corto.', $table));
      Redirect::back();
    }

    // Validar la existencia de un duplicado
    if (is_file(MODELS.$filename.$keyword.'.php')) {
      Flasher::error(sprintf('Ya existe el modelo %s.', $filename.$keyword));
      Redirect::back();
    }

    // Validar la existencia de la template de modelo
    if (!is_file($template)) {
      Flasher::error(sprintf('No existe la plantilla %s.', $template));
      Redirect::back();
    }
    
    // Cargar contenido del archivo
    $php = @file_get_contents($template);
    $php = str_replace('[[REPLACE]]', $filename, $php);
    $php = str_replace('[[REPLACE_TABLE]]', (empty($table) ? $filename : $table), $php);

    // Validar si es necesario procesar el esquema de Modelo
    if (!empty($scheme)) {
      $scheme = str_replace([' ','.'], '', $scheme);
      $scheme = explode(',', $scheme);

      foreach ($scheme as $i => $s) {
        if ($i === 0) {
          $scheme_s .= sprintf("public $%s;\n", $s);
        } else {
          $scheme_s .= sprintf("\tpublic $%s;\n", $s);
        }
      }
    }

    // Reemplazar el esquema aunque no exista, para dejarlo en blanco en el archivo final
    $php = str_replace('[[REPLACE_SCHEME]]', $scheme_s, $php);

    // Guardar el contenido del Modelo en un nuevo archivo
    if (file_put_contents(MODELS.$filename.$keyword.'.php', $php) === false)  {
      Flasher::new(sprintf('Ocurrió un problema al crear el modelo %s.', $template), 'danger');
      Redirect::back();
    }

    Flasher::new(sprintf('Modelo <b>%s</b> creado con éxito.', $filename.$keyword));
    Redirect::back();
  }
}