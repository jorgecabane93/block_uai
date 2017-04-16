<?php
/**
 * Clase que define los links a funcionalidades propias de la UAI
 * @author Webcursos UAI
 *
 */
class block_uai extends block_base {

	/** @var string The name of the block */
	public $blockname = null;

	/**
	 * Función de inicialización del bloque
	 */
	public function init() {
		$this->blockname = get_class($this);
		$this->title = get_string('UAI', 'block_uai');
	}

	/**
	 * Returns the attributes to set for this block
	 *
	 * This function returns an array of HTML attributes for this block including
	 * the defaults.
	 * {@link block_tree::html_attributes()} is used to get the default arguments
	 * and then we check whether the user has enabled hover expansion and add the
	 * appropriate hover class if it has.
	 *
	 * @return array An array of HTML attributes
	 */
	public function html_attributes() {
		$attributes = parent::html_attributes();
		if (!empty($this->config->enablehoverexpansion) && $this->config->enablehoverexpansion == 'yes') {
			$attributes['class'] .= ' block_js_expansion';
		}
		$attributes['class'] .= ' block_uai';
		return $attributes;
	}

	/**
	 * Set the applicable formats for this block to all
	 * @return array
	 */
	function applicable_formats() {
		return array('all' => true);
	}


	/**
	 * Returns the role that best describes the navigation block... 'navigation'
	 *
	 * @return string 'navigation'
	 */
	public function get_aria_role() {
		return 'navigation';
	}

	function has_config() {
		return true;
	}
	/**
	 * Agrega los llamados a las librerías javascript necesarias (non-PHPdoc)
	 * @see block_base::get_required_javascript()
	 */
	function get_required_javascript() { //habilita la funcion de utlizar el js del menu de arbol
		global $CFG;
		$arguments = array(
				'id' => $this->instance->id,
				'instance' => $this->instance->id,
				'candock' => $this->instance_can_be_docked());
		$this->page->requires->yui_module('moodle-block_navigation-navigation',
				'M.block_navigation.init_add_tree', array($arguments));
		// user_preference_allow_ajax_update('docked_block_instance_'.$this->instance->id, PARAM_INT);
	}

	/**
	 * Muestra Toolbox: Ranking actual de los cursos del usuario y link
	 * a las páginas de detalles.
	 *
	 * @return string HTML a mostrar
	 */
	function toolbox() { //REVISAR obtenemos la funcion de contenido de toolbox

		global $USER, $CFG, $DB, $PAGE, $COURSE;
			
		if($CFG->block_uai_local_modules && !in_array('toolbox',explode(',',$CFG->block_uai_local_modules))) {
			return false;
		}

		$categoryid = 0;
		if($COURSE && $COURSE->id > 1) {
		    $categoryid = $COURSE->category;
		} elseif ($PAGE->context instanceof context_coursecat) {
		    $categoryid = intval($PAGE->context->__get('instanceid'));
		}
		
		if(!$categoryid) {
		    return false;
		}
		
		    $modulestatsnode = navigation_node::create(
		        get_string('modulestats', 'local_uai'),
		        new moodle_url("/local/uai/modulestats.php", array('id'=>$categoryid)),
		        navigation_node::TYPE_CUSTOM, null, null,
		        new pix_icon('i/report', get_string('modulestats', 'local_uai'))); //url para ver sedes
		
		    $rootnode = navigation_node::create(get_string('pluginname', 'local_uai'));
		
		    $rootnode->add_node($modulestatsnode);
		
		    return $rootnode;		
		
		require_once($CFG->dirroot.'/local/toolbox/lib.php');
		require_once($CFG->dirroot.'/course/lib.php');
		require_once($CFG->dirroot.'/lib/accesslib.php');
		require_once($CFG->dirroot.'/lib/moodlelib.php');
		require_once($CFG->dirroot.'/lib/weblib.php');

		$html='';

		//Link to the page of ToolBox (view.php)
		$toolbox = $CFG->wwwroot.'/local/toolbox/view.php';
		$summary = get_summary();
		$valor = $summary['nivel'];
		$textscore = get_score_text($valor);
		$imagescore = get_img_source($valor);


		$context = context_course::instance($COURSE->id); //obtenemos el contexto del curso

		//revisamos la capacidad que tiene el usuario
		if (has_capability('local/toolbox:viewtoolboxstudent', $context)) {
			//alumno
			$html .= '<div><b><a href="'.$toolbox.'?view=miscursos">'.get_string('minivel','local_toolbox').$textscore.' ('.$summary["nivel"].')</a></b></div>
					<div><img src="'.$imagescore .'"></div>
							<hr>
							<div><a href="'.$toolbox.'?view=acerca">'.get_string('acerca', 'local_toolbox').'</a></div>';


		}
		elseif (has_capability('local/toolbox:viewtoolboxteacher', $context)) {
			//profesor
			$ranking = get_ranking();
			$userid = $USER->id;
			$rank = null;

			if(isset($ranking[$userid]))
				$rank = $ranking[$userid]->rank;

			if ($rank){ // si tiene ranking, desplegamos su posicion en el ranking
				$totalProfesores = count($ranking);
				$html .= '<div><b>'.get_string('minivel','local_toolbox').$textscore.' ('.$summary["nivel"].')</b></div>
						<div><img src="'.$imagescore .'"></div>
								<div><b>'.get_string('miranking', 'local_toolbox') .$rank .get_string('mirankingde', 'local_toolbox') .$totalProfesores .'</b></div>
										<hr>
										<div><a href="'.$toolbox.'?view=miscursos">'.get_string('miscursos', 'local_toolbox').'</a></div>
												<div><a href="'.$toolbox.'?view=acerca">'.get_string('acerca', 'local_toolbox').'</a></div>';
			}

			else { //si no tiene ranking, se le muestra "Sin ranking"
				$totalProfesores = count($ranking);
				$html.='<div><b>'.get_string('minivel','local_toolbox').$textscore.' ('.$summary["nivel"].')</b></div>
						<div><img src="'.$imagescore .'"></div>
								<div><b>'.get_string('miranking', 'local_toolbox').get_string('sinranking', 'local_toolbox').'</b></div>
										<hr>
										<div><a href="'.$toolbox.'?view=miscursos">'.get_string('miscursos', 'local_toolbox').'</a></div>
												<div><a href="'.$toolbox.'?view=acerca">'.get_string('acerca', 'local_toolbox').'</a></div>';
			}



		}
		elseif (has_capability('local/toolbox:viewtoolboxmanager', $context)){
			//decano y rector y manager
			$ranking = get_ranking();
			$userid = $USER->id;
			$rank = $ranking[$userid]->rank;
			$totalProfesores = count($ranking);
			$html .= '<div><b>'.get_string('minivel','local_toolbox').$textscore.' ('.$summary["nivel"].')</b></div>
					<div><img src="'.$imagescore .'"></div>
							<div><b>'.get_string('miranking', 'local_toolbox') .$rank .get_string('mirankingde', 'local_toolbox') .$totalProfesores .'</b></div>
									<hr>
									<div><a href="'.$toolbox.'?view=miscursos">'.get_string('miscursos', 'local_toolbox').'</a></div>
											<div><a href="'.$toolbox.'?view=ranking&rank='.$summary["uai"].'">'.get_string('ranking', 'local_toolbox').'</a></div>
													<div><a href="'.$toolbox.'?view=acerca">'.get_string('acerca', 'local_toolbox').'</a></div>';
		}

		elseif (has_capability('local/toolbox:viewtoolboxuser', $context)){
			/////otro usuario
			$html="";
		}
		else{
			$html = "";
		}
		$html= '

				<li class="type_course depth_2 collapsed contains_branch">
				<p class="tree_item branch">
				<span tabindex="0">'.get_string('toolbox', 'block_uai').'</span>
						</p>
						<ul>
						<li class="type_custom depth_3 item_with_icon">
						'.$html.'
								</li>
								</ul>
								</li>
								';
		return $html;
	}

	/**
	 * Muestra la bibliografía de un curso.
	 *
	 * @return string
	 */
	function bibliography(){ //desplegamos el iframe de bibliografia

		global $CFG, $COURSE, $PAGE;

		if($CFG->block_uai_local_modules && !in_array('bibliography',explode(',',$CFG->block_uai_local_modules))) {
			return false;
		}

		$courseid = $COURSE->id;
		if ($courseid<=1){
			return false;
		} // si estamos en la pagina principal (courseid = 1 no desplegamos bibliografia

		$coursesn = $COURSE->shortname;

		$biblio = new moodle_url("/local/bibliography/bibliography.php?coursesn=$coursesn&&courseid=$courseid"); //obtenemos de la pagina el shortname del curso y el id del curso

		$context = context_course::instance($courseid);

		//revisamos si tiene la capacidad de modificar la bibliografia
		if(!has_capability('local/bibliography:modify', $context)) {
			$biblio2 = '<br>'.$CFG->local_bibliography_student_help;
			$biblio2 .= '<a href="' . $CFG->wwwroot . '/local/bibliography/suggest.php?courseid='. $courseid .'">'.get_string('suggestlink','local_bibliography').'</a>';
		} else {
			$biblio2 = '<br>'.$CFG->local_bibliography_teacher_help;
		}


		$bibl='
				<li class="type_course depth_2 collapsed contains_branch">
				<p class="tree_item branch">
				<span tabindex="0">'.get_string('biblio', 'block_uai').'</span>
						</p>
						<ul>
						<li class="type_custom depth_3 item_with_icon">
						<iframe id="frame1" width="180" height="175" src="'.$biblio.'" style="border:1px dotted #CCC; border-radius:5px;">'.get_string('biblio', 'block_uai').'</iframe>
								'.$biblio2.'
										</li>
										</ul>
										</li>
										';

		return $bibl;

	}

	/**
	 * Muestra los links de eMarking
	 *
	 * @return string
	 */
	function emarking(){ // desplegamos el contenido de eMarking

		global $COURSE, $CFG, $PAGE;

		if($CFG->block_uai_local_modules && !in_array('emarking',explode(',',$CFG->block_uai_local_modules))) {
			return false;
		}

		$context = $PAGE->context;

		$course = $PAGE->course;
		$courseid = $course->id;

		if ($courseid==null || $courseid==1 || !has_capability('mod/assign:grade', $context)){ //checkeamos si tenemos la capacidad
			return false;
		}

		$nodonewprintorder = navigation_node::create(
				get_string('blocknewprintorder', 'block_uai'),
				new moodle_url("/course/modedit.php", array("sr"=>0,"add"=>"emarking","section"=>0,"course"=>$courseid)), //url para enlazar y ver información de facebook
				navigation_node::TYPE_CUSTOM,
				null, null, new pix_icon('t/portfolioadd', get_string('newprintorder', 'mod_emarking')));
		
		$nodomyexams = navigation_node::create(
				get_string('blockmyexams', 'block_uai'),
				new moodle_url("/mod/emarking/print/exams.php", array("course"=>$courseid)), //url para enlazar y ver información de facebook
				navigation_node::TYPE_CUSTOM,
				null, null, new pix_icon('a/view_list_active', get_string('myexams', 'mod_emarking')));
		
		$nodocycle = navigation_node::create(
				get_string('cycle', 'block_uai'),
				new moodle_url("/mod/emarking/reports/cycle.php", array("course"=>$courseid)), //url para enlazar y ver información de facebook
				navigation_node::TYPE_CUSTOM,
				null, null, new pix_icon('i/course', get_string('cycle', 'mod_emarking')));


		$rootnode = navigation_node::create(get_string('blockexams', 'block_uai'));
		$rootnode->add_node($nodonewprintorder);
		$rootnode->add_node($nodomyexams);
		$rootnode->add_node($nodocycle);
		return $rootnode;
			
	}
	

	/**
	 * URL a local/reportes, módulo de reportes de la UAI.
	 *
	 * @return string URL al index del módulo reportes
	 */
	function reportes(){
		global $COURSE, $CFG, $PAGE, $USER;

		if($CFG->block_uai_local_modules && !in_array('reportes',explode(',',$CFG->block_uai_local_modules))) {
			return false;
		}

		$course = $PAGE->course;

		if(!$course || !has_capability('local/reportes:view', $PAGE->context) || $course->id <= 1)
			return false;

		$urlreportes = new moodle_url("/local/reportes/index.php", array("courseid"=>$course->id));

		$rootnode = navigation_node::create(
				get_string('reportes', 'block_uai'),
				$urlreportes,
				navigation_node::TYPE_CUSTOM,
				null, null, new pix_icon('t/scales', get_string('reportes', 'block_uai')));

		return $rootnode;
	}

	function reserva_salas(){ //desplegamos el contenido de reserva de salas

		global $USER, $CFG, $DB, $COURSE, $PAGE;

		if($CFG->block_uai_local_modules
		&& !in_array('reservasalas',explode(',',$CFG->block_uai_local_modules))) {
			return false;
		}

		$nodosedes = navigation_node::create(
				get_string('ajsedes', 'block_uai'),
				new moodle_url("/local/reservasalas/sedes.php"),
				navigation_node::TYPE_CUSTOM, null, null,
				new pix_icon('i/report', get_string('ajsedes', 'block_uai'))); //url para ver sedes
		$nodosalas = navigation_node::create(
				get_string('ajmodversal', 'block_uai'),
				new moodle_url("/local/reservasalas/salas.php"),
				navigation_node::TYPE_CUSTOM, null, null,
				new pix_icon('i/report', get_string('ajsedes', 'block_uai'))); //url para ver salas creadas
		$nodoedificios = navigation_node::create(
				get_string('ajmodvered', 'block_uai'),
				new moodle_url("/local/reservasalas/edificios.php"),
				navigation_node::TYPE_CUSTOM, null, null,
				new pix_icon('i/report', get_string('ajsedes', 'block_uai'))); //url para ver edificios creados
		/*
		$nodohistorial = navigation_node::create(
				get_string('historial', 'block_uai'),
				new moodle_url("/local/reservasalas/historial.php"),
				navigation_node::TYPE_CUSTOM, null, null,
				new pix_icon('i/report', get_string('ajsedes', 'block_uai'))); //url para ver el historial de todas las reservas
		*/
		$nodoreservar = navigation_node::create(
				get_string('reservar', 'block_uai'),
				new moodle_url("/local/reservasalas/reservar.php"),
				navigation_node::TYPE_CUSTOM, null, null,
				new pix_icon('i/report', get_string('ajsedes', 'block_uai'))); //url para reservar salas
		$nodomisreservas = navigation_node::create(
				get_string('misreservas', 'block_uai'),
				new moodle_url("/local/reservasalas/misreservas.php"),
				navigation_node::TYPE_CUSTOM, null, null,
				new pix_icon('i/report', get_string('ajsedes', 'block_uai'))); //url para ver las reservas de un usuario
		$nodobloquear = navigation_node::create(
				get_string('bloquear', 'block_uai'),
				new moodle_url("/local/reservasalas/bloquear.php"),
				navigation_node::TYPE_CUSTOM, null, null,
				new pix_icon('i/report', get_string('ajsedes', 'block_uai'))); //url para bloquear usuarios
		$nododesbloquear = navigation_node::create(
				get_string('desbloq', 'block_uai'),
				new moodle_url("/local/reservasalas/desbloquear.php"),
				navigation_node::TYPE_CUSTOM, null, null,
				new pix_icon('i/report', get_string('ajsedes', 'block_uai'))); //url para desbloquar usuarios
		/*
		$nodoestadisticas = navigation_node::create(
				get_string('statistics', 'block_uai'),//'Estadísticas',
				new moodle_url("/local/reservasalas/estadisticas.php"),
				navigation_node::TYPE_CUSTOM, null, null,
				new pix_icon('i/report', get_string('ajsedes', 'block_uai')));
		*/
		$nodoreservasporusuario = navigation_node::create(
				get_string('viewuserreserves', 'block_uai'),//'Ver reservas por usuario',
				new moodle_url("/local/reservasalas/reservasusuarios.php"),
				navigation_node::TYPE_CUSTOM, null, null,
				new pix_icon('i/report', get_string('ajsedes', 'block_uai')));
		$nododiagnostico = navigation_node::create(
				get_string('diagnostic', 'block_uai'),//'Diagnóstico',
				new moodle_url("/local/reservasalas/diagnostico.php"),
				navigation_node::TYPE_CUSTOM, null, null,
				new pix_icon('i/report', get_string('ajsedes', 'block_uai')));
		$nodoresources = navigation_node::create(
				get_string('urlresources', 'block_uai'),
				new moodle_url("/local/reservasalas/resources.php"),
				navigation_node::TYPE_CUSTOM, null, null,
				new pix_icon('i/report', get_string('ajsedes', 'block_uai')));
		$nodoupload = navigation_node::create(
				get_string('upload', 'block_uai'),//'upload',
				new moodle_url("/local/reservasalas/upload.php"),
				navigation_node::TYPE_CUSTOM, null, null,
				new pix_icon('i/report', get_string('ajsedes', 'block_uai')));
		$nodosearch = navigation_node::create(
				get_string('search', 'block_uai'),
				new moodle_url("/local/reservasalas/search.php"),
				navigation_node::TYPE_CUSTOM, null, null,
				new pix_icon('i/report', get_string('ajsedes', 'block_uai')));
		
		$context = context_system::instance();
			
		$rootnode = navigation_node::create(get_string('reservasal', 'block_uai'));

		$rootnode->add_node($nodoreservar);


			
		
		    if(has_capability('local/reservasalas:advancesearch', $context)) {
			    
		    	$rootnode->add_node($nodosearch);
		}
		
			if(has_capability('local/reservasalas:administration', $context)||
				has_capability('local/reservasalas:bockinginfo', $context)||
				has_capability('local/reservasalas:blocking', $context)) {	
			$nodesettings = navigation_node::create(
					get_string('ajustesrs', 'block_uai'),
					null,
					navigation_node::TYPE_UNKNOWN);

			$rootnode->add_node($nodesettings);
			}
			if(has_capability('local/reservasalas:administration', $context)) {
				$nodesettings->add_node($nodosalas);
				$nodesettings->add_node($nodoedificios);
				$nodesettings->add_node($nodosedes);
				$nodesettings->add_node($nodoresources);
			
			}

			if(has_capability('local/reservasalas:bockinginfo', $context)){ //revisamos la capacidad del usuario
				//administrador
				//$nodesettings->add_node($nodohistorial);
				$nodesettings->add_node($nodoreservasporusuario);
				//$nodesettings->add_node($nodoestadisticas);
				$nodesettings->add_node($nododiagnostico);
				
			}
		if(has_capability('local/reservasalas:blocking', $context)){
				$nodeusuarios = navigation_node::create(
						get_string('usuarios', 'block_uai'),
						null,
						navigation_node::TYPE_UNKNOWN);
				$nodeusuarios->add_node($nododesbloquear);
				$nodeusuarios->add_node($nodobloquear);
				$rootnode->add_node($nodeusuarios);
			}	
			if(isset($CFG->local_uai_debug) && $CFG->local_uai_debug==1) {
				
				if(has_capability('local/reservasalas:upload', $context)){
					$rootnode->add_node($nodoupload);
				
				
			}
			}

	 //alumnos

		if(!has_capability('local/reservasalas:advancesearch', $context)) {
			$rootnode->add_node($nodomisreservas);
		}

		return $rootnode;
	}

	function print_orders(){ //desplegamos las ordenes de impresion de evaluaciones

		global $DB, $USER, $CFG, $COURSE, $PAGE;

		if($CFG->block_uai_local_modules && !in_array('emarking',explode(',',$CFG->block_uai_local_modules))) {
			return false;
		}

		if(!has_capability('mod/emarking:printordersview', $PAGE->context))
		    return false;
		
		$categoryid = 0;
		if($COURSE && $COURSE->id > 1) {
		    $categoryid = $COURSE->category;
		} elseif ($PAGE->context instanceof context_coursecat) {
		    $categoryid = intval($PAGE->context->__get('instanceid'));
		}
		
		if(!$categoryid) {
		    return false;
		}
		
		$rootnode = navigation_node::create(get_string('printorders', 'mod_emarking'));
		
		$url = new moodle_url("/mod/emarking/print/printorders.php", array("category"=>$categoryid));

		$nodeprintorders = navigation_node::create(
				get_string('printorders', 'mod_emarking'),
				$url,
				navigation_node::TYPE_CUSTOM,
				null, null, new pix_icon('t/print', get_string('printorders', 'mod_emarking')));
		
		$url = new moodle_url("/mod/emarking/reports/costcenter.php", array("category"=>$categoryid));
		
		$nodecostreport = navigation_node::create(
				get_string('costreport', 'mod_emarking'),
				$url,
				navigation_node::TYPE_CUSTOM,
				null, null, new pix_icon('t/ranges', get_string('printorders', 'mod_emarking')));
		
		$url = new moodle_url("/mod/emarking/reports/costconfig.php", array("category"=>$categoryid));
		
		$nodecostconfiguration = navigation_node::create(
				get_string('costsettings', 'mod_emarking'),
				$url,
				navigation_node::TYPE_CUSTOM,
				null, null, new pix_icon('a/setting', get_string('printorders', 'mod_emarking')));

		$rootnode->add_node($nodeprintorders);
		$rootnode->add_node($nodecostreport);
		$rootnode->add_node($nodecostconfiguration);
		
		return $rootnode;
	}

	function facebook(){ //Show facebook content
		global $USER, $CFG, $DB, $COURSE;
		
		//$context = context_block::instance($COURSE->id);

		if($CFG->block_uai_local_modules && !in_array('facebook',explode(',',$CFG->block_uai_local_modules))) {
			return false;
		}

		$nodoconnect = navigation_node::create(
				get_string('connect', 'block_uai'),
				new moodle_url("/local/facebook/connect.php"), //url para enlazar y ver información de facebook
				navigation_node::TYPE_CUSTOM,
				null, null);

		
		$nodoinfo = navigation_node::create(
				get_string('info', 'block_uai'),
				new moodle_url("/local/facebook/connect.php"), //url para enlazar y ver información de facebook
				navigation_node::TYPE_CUSTOM,
				null, null);
		
		$nodoapp = navigation_node::create(
				get_string('goapp', 'block_uai'),
				$CFG->fbk_url,
				navigation_node::TYPE_CUSTOM,
				null, null);

		$rootnode = navigation_node::create(get_string('facebook', 'block_uai'));

		$context = context_system::instance();

		$exist = $DB->get_record('facebook_user',array('moodleid'=>$USER->id,'status'=>'1'));

		if($exist==false){
			$rootnode->add_node($nodoconnect);
			
		} else {
			$rootnode->add_node($nodoinfo);
			$rootnode->add_node($nodoapp);
			$facebook =''.$CFG->wwwroot.'/blocks/uai/img/like.png" height="20" width="20"';
		}

			return $rootnode;
		
	}

	// Bloque de Paperattendance.
	function paperattendance() {
		global $COURSE, $PAGE, $CFG, $USER, $DB;
	
		if($CFG->block_uai_local_modules && !in_array('paperattendance',explode(',',$CFG->block_uai_local_modules))) {
			return false;
		}
	
		$categoryid = optional_param("categoryid", 1, PARAM_INT);
		$context = $PAGE->context;
		
		//new feature for the secretary to see printsearch and upload from everywhere
		$sqlcategory = "SELECT cc.*
					FROM {course_categories} cc
					INNER JOIN {role_assignments} ra ON (ra.userid = ?)
					INNER JOIN {role} r ON (r.id = ra.roleid)
					INNER JOIN {context} co ON (co.id = ra.contextid)
					WHERE cc.id = co.instanceid AND r.shortname = ?";
		$categoryparams = array($USER->id, "secre_pregrado");
		$secretaryhascategory = $DB->get_record_sql($sqlcategory, $categoryparams);
	
		$rootnode = navigation_node::create(get_string('paperattendance', 'block_uai'));
	
		//url para subir un pdf escaneado del curso
		$uploadattendanceurl = new moodle_url("/local/paperattendance/upload.php", array("courseid" => $COURSE->id,"categoryid" => $categoryid));
		$nodouploadattendance = navigation_node::create(
				get_string('uploadpaperattendance', 'block_uai'),
				$uploadattendanceurl,
				navigation_node::TYPE_CUSTOM,
				null, null, new pix_icon('i/backup', get_string('uploadpaperattendance', 'block_uai')));
	
		//url para agregar, editar y eliminar modulos
		$modulesattendanceurl = new moodle_url("/local/paperattendance/modules.php");
		$nodomodulesattendance = navigation_node::create(
				get_string('modulespaperattendance', 'block_uai'),
				$modulesattendanceurl,
				navigation_node::TYPE_CUSTOM,
				null, null, new pix_icon('i/calendar', get_string('modulespaperattendance', 'block_uai')));
	
		//url para descargar pdf del listado del curso para tomar asistencia
		$printattendanceurl = new moodle_url("/local/paperattendance/print.php", array("courseid" => $COURSE->id,"categoryid" =>$categoryid));
		$nodoprintattendance = navigation_node::create(
				get_string('printpaperattendance', 'block_uai'),
				$printattendanceurl,
				navigation_node::TYPE_CUSTOM,
				null, null, new pix_icon('e/print', get_string('printpaperattendance', 'block_uai')));
	
		//url para ver el historial de pdfs escaneados del curso y sus asistencias digitales
		$historyattendanceurl = new moodle_url("/local/paperattendance/history.php", array("courseid" => $COURSE->id));
		$nodohistoryattendance = navigation_node::create(
				get_string('historypaperattendance', 'block_uai'),
				$historyattendanceurl,
				navigation_node::TYPE_CUSTOM,
				null, null, new pix_icon('i/grades', get_string('historypaperattendance', 'block_uai')));
		
		//url para ver las discusiones de asistencia pendientes
		$discussionattendanceurl = new moodle_url("/local/paperattendance/discussion.php", array(
				"courseid" => $COURSE->id
		));
		$nododiscussionattendance = navigation_node::create(
				get_string('discussionpaperattendance', 'block_uai'),
				$discussionattendanceurl,
				navigation_node::TYPE_CUSTOM,
				null, null, new pix_icon('i/cohort', get_string('discussionpaperattendance', 'block_uai')));
		
		//url para print search
		$printsearchurl = new moodle_url("/local/paperattendance/printsearch.php", array("courseid" => $COURSE->id,"categoryid" => $categoryid));
		$nodoprintsearch = navigation_node::create(
				get_string('printsearchpaperattendance', 'block_uai'),
				$printsearchurl,
				navigation_node::TYPE_CUSTOM,
				null, null, new pix_icon('t/print', get_string('printsearchpaperattendance', 'block_uai')));
	
		if(has_capability('local/paperattendance:upload', $context) || $secretaryhascategory){
			$rootnode->add_node($nodouploadattendance);
		}
		if(has_capability('local/paperattendance:modules', $context)){
			$rootnode->add_node($nodomodulesattendance);
		}
		if(has_capability('local/paperattendance:printsearch', $context) || $secretaryhascategory){
			$rootnode->add_node($nodoprintsearch);
		}
	
		if($COURSE->id > 1 && $COURSE->idnumber != NULL){
			if(has_capability('local/paperattendance:print', $context) || has_capability('local/paperattendance:printsecre', $context)){
				$rootnode->add_node($nodoprintattendance);
			}
			if(has_capability('local/paperattendance:history', $context)){
				$rootnode->add_node($nodohistoryattendance);
				$rootnode->add_node($nododiscussionattendance);
			}
		}
	
		return $rootnode;
	}
	
	function syncomega(){
		global $CFG;
	
		if($CFG->block_uai_local_modules
				&& !in_array('syncomega',explode(',',$CFG->block_uai_local_modules))) {
					return false;
			}
			$nodohistorial = navigation_node::create(
					get_string('synchistory', 'block_uai'),
					new moodle_url("/local/sync/history.php"),
					navigation_node::TYPE_CUSTOM, null, null,
					new pix_icon('i/siteevent', get_string('synchistory', 'block_uai'))); //url para reservar salas;

			$nodocreate = navigation_node::create(
					get_string('synccreate', 'block_uai'),
					new moodle_url("/local/sync/create.php"),
					navigation_node::TYPE_CUSTOM, null, null,
					new pix_icon('e/new_document', get_string('synccreate', 'block_uai')));

			$nodorecord = navigation_node::create(
					get_string('syncrecord', 'block_uai'),
					new moodle_url("/local/sync/record.php"),
					navigation_node::TYPE_CUSTOM, null, null,
					new pix_icon('e/fullpage', get_string('syncrecord', 'block_uai')));



			$context = context_system::instance();
			if(has_capability('local/sync:history', $context)) {
				$rootnode = navigation_node::create(get_string('syncomega', 'block_uai'));
				$rootnode->add_node($nodocreate);
				$rootnode->add_node($nodorecord);
				$rootnode->add_node($nodohistorial);
				return $rootnode;
			}
			else{
				return false;
			}
	}

	public function get_content() {
			
		if ($this->content !== null) { //si el contenido ya esta generado, no se genera una 2da vez
			return $this->content;
		}

		block_settings::$navcount++;

		$this->content         =  new stdClass;

		if (!isloggedin()) { // si no esta conectado, el bloque no se muestra
			$this->content->text = '';
			return $this->content;
		}

		$root = navigation_node::create(
				"UAI",
				null,
				navigation_node::TYPE_ROOTNODE,
				null,
				null);

		if($nodereservasalas = $this->reserva_salas()){
			$root->add_node($nodereservasalas);
		}
		if($nodeprintorders = $this->print_orders()){
			$root->add_node($nodeprintorders);
		}
		if($nodeemarking = $this->emarking()){
			$root->add_node($nodeemarking);
		}
		if($nodefacebook = $this->facebook()){
			$root->add_node($nodefacebook);
		}
		if($nodereportes = $this->reportes()){
			$root->add_node($nodereportes);
		}
		if($nodetoolbox = $this->toolbox()){
			$root->add_node($nodetoolbox);
		}
		if($nodepaperattendance = $this->paperattendance()){
			$root->add_node($nodepaperattendance);
		}
		if($nodesyncomega = $this->syncomega()){
			$root->add_node($nodesyncomega);
		}

		$renderer = $this->page->get_renderer('block_uai');
		$this->content->text = $renderer->uai_tree($root);
		$this->content->footer = '';
		return $this->content;
	}
	
}
