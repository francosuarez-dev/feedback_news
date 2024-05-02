<?php
/*
Plugin Name: FeedBack Noticias
Plugin URI: https://github.com/francosuarez-dev/feedback_news
Description: Permite a tus lectores reaccionar al articulo que estan leyendo.
Author: Franco Suarez
Author URI: https://www.github.com/francosuarez-dev
Version: 1.1
License: GPLv2 or later.
*/


define( 'FEEDBACK_PATH', plugin_dir_path( __FILE__ ) );


/*
 * @description Hook que se ejecuta al activar el plugin
 */
register_activation_hook( __FILE__, 'feedback_instala' );



/*
 * @description A�adir Scripts
 */
add_action( 'wp_enqueue_scripts', 'ajax_feedback_hoy_enqueue_scripts' );

function ajax_feedback_hoy_enqueue_scripts() {
	
	if( is_single() ) {
		wp_enqueue_style( 'css_feed', plugins_url( '/css_feed.css', __FILE__ ) );
		
	}
	
	wp_enqueue_script( 'scriptjs', plugins_url( '/scriptjs.js', __FILE__ ), array('jquery'), '1.0', true );
	
	wp_localize_script( 'scriptjs', 'setfeedback', array('ajaxurl' => admin_url( 'admin-ajax.php' )
	));
}


/*
 * @description Mostramos Calificaciones
 */
 
add_filter( 'the_content', 'feedback_display');

function feedback_display( $content ) {
	
	$full_content = "";

	if ( is_single() ) {
		
		$id_post = get_the_ID();
		$content_feed = '<div><h4 class="block-title"><span>QU&Eacute; TE GENERA ESTE ARTICULO?</span></h4>';

		for($i=5;$i>=1;$i--){
			
			switch ($i){
				case 5:
					$texto = "ME IMPORTA";
					break;
				case 4:
					$texto = "ME GUSTA";
					break;
				case 3:
					$texto = "ME DA IGUAL";
					break;
				case 2:
					$texto = "ME ABURRE";
					break;
				case 1:
					$texto = "ME ENOJA";
					break;
			}
			
			$content_feed .= '<div class="iter-user-feedback-rating-blocks">';
			$content_feed .='<a class="feedback-function" href="#" data-id="' . get_the_ID().'" data-calif="'.$i.'">';
			$content_feed .='<div class="block-value"><span id="porc'.$i.'" class="block-value-label">'.obtienePorcenFeed($i,$id_post).'%</span><div class="block-value-percent p75">';
			$content_feed .='</div>
						</div>
						<div class="block-text">
						<span class="block-text-label">'.$texto.'</span>
						</div>
						</a>
						</div>';
		}
		
		$div_final= '<div class="final-feedback"></div></div>';

		$full_content= $content.$content_feed.$div_final;

	}
	

	return $full_content;

}







/*
 * @description Funci�n que crea las tablas en la activaci�n del plugin
 */
 
function feedback_instala(){
	global $wpdb;
	$table_name= $wpdb->prefix . "feedback_news";
	$sql = " CREATE TABLE $table_name(
	id mediumint( 9 ) NOT NULL AUTO_INCREMENT ,
	id_post bigint (20) NOT NULL ,
	ip_user text NOT NULL ,
	calif int (3) NOT NULL ,
	PRIMARY KEY ( `id` )
	) ;";

	//upgrade contiene la funci�n dbDelta la cu�l revisar� si existe la tabla o no
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
 
    //creamos la tabla
    dbDelta($sql);

}


add_action( 'wp_ajax_nopriv_inserta_feed', 'inserta_feed' );
add_action( 'wp_ajax_inserta_feed', 'inserta_feed' );

/*
 * @description Funcion que obtiene direccion IP del usuario
 */
 
function getRealIP()
{

	if (isset($_SERVER["HTTP_CLIENT_IP"]))
	{
		return $_SERVER["HTTP_CLIENT_IP"];
	}
	elseif (isset($_SERVER["HTTP_X_FORWARDED_FOR"]))
	{
		return $_SERVER["HTTP_X_FORWARDED_FOR"];
	}
	elseif (isset($_SERVER["HTTP_X_FORWARDED"]))
	{
		return $_SERVER["HTTP_X_FORWARDED"];
	}
	elseif (isset($_SERVER["HTTP_FORWARDED_FOR"]))
	{
		return $_SERVER["HTTP_FORWARDED_FOR"];
	}
	elseif (isset($_SERVER["HTTP_FORWARDED"]))
	{
		return $_SERVER["HTTP_FORWARDED"];
	}
	else
	{
		return $_SERVER["REMOTE_ADDR"];
	}

}


/*
 * @description Funcion que inserta datos de feedback
 * Sistema Valores Calificacion:
 * ME IMPORTA = 5
 * ME GUSTA = 4
 * ME DA IGUAL = 3
 * ME ABURRE = 2
 * ME ENOJA = 1
 */
 
function inserta_feed(){
	
	$respuesta=array();
	
	$id_post = $_POST['post_id'];
	$ip_user = getRealIP();
	$calif=$_REQUEST['calif'];
	
	global $wpdb;
	$table_name= $wpdb->prefix . "feedback_news";
	
	//Consultamos si usuario voto ya en el Post
	$consulta = "SELECT * FROM $table_name WHERE `id_post` = $id_post AND `ip_user` = '$ip_user'";
	$wpdb->query($consulta);
	$total_resul = $wpdb->num_rows;
	
	if ($total_resul!=0){
		//Usario Ya voto
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			$respuesta['ok']=false;
			$respuesta['msg']="Ya dejo su opinion";
			echo json_encode($respuesta);
			die();
		}
		else {
			wp_redirect( get_permalink( $_REQUEST['post_id'] ) );
			exit();
		}
	}else{
		
		//Registramos Voto si no lo hizo
		$query = "INSERT INTO $table_name (id_post,ip_user,calif) VALUES ('$id_post','$ip_user','$calif')";
			
			if($wpdb->query($query)){
				if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
					$respuesta['ok']=true;
					$respuesta['msg']="Gracias por su opinion";
					
					for($i=5;$i>=1;$i--){
						$respuesta["val".$i]=obtienePorcenFeed($i,$id_post);
					}
					
					echo json_encode($respuesta);
					die();
				}
				else {
					wp_redirect( get_permalink( $_REQUEST['post_id'] ) );
					exit();
				}
			}else{
				if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
					$respuesta['ok']=false;
					$respuesta['msg']="Error intente nuevamente";
					echo json_encode($respuesta);
					die();
				}
				else {
					wp_redirect( get_permalink( $_REQUEST['post_id'] ) );
					exit();
				}
			}
	}
	
}

/*
 * @description Funcion que obtiene datos de feedback
 * Sistema Valores Calificacion:
 * ME IMPORTA = 5
 * ME GUSTA = 4
 * ME DA IGUAL = 3
 * ME ABURRE = 2
 * ME ENOJA = 1
 */
 
function obtienePorcenFeed ($calif,$id_post){
	global $wpdb;
	
	$table_name= $wpdb->prefix . "feedback_hoy";
	
	$consulta = "SELECT * FROM $table_name WHERE `id_post` = $id_post";
	$wpdb->query($consulta);
	$total_votos=$wpdb->num_rows;
	
	
	$consulta2 = "SELECT * FROM $table_name WHERE `id_post` = $id_post AND `calif` = $calif";
	$wpdb->query($consulta2);
	$total_calif=$wpdb->num_rows;
	
	if($total_calif!=0){
		$porcen = $total_calif * 100 / $total_votos;
	}else{
		$porcen = 0;
	}
	
	return round($porcen);
}


?>