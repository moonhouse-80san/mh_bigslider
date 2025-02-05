<?php
/**
 * @class mh_bigslider
 * @author Moonhouse (mbg1346@naver.com)
 * @brief 최근 게시물을 출력하는 위젯 (PHP 8 호환)
 * @version 0.2
 **/

class mh_bigslider extends WidgetHandler {

	/**
	 * @brief 위젯 실행 함수
	 **/
	public function proc($args) {
		// 모델 객체 가져오기
		$oModuleModel = getModel('module');
		$oDocumentModel = getModel('document');
		Context::set('oModuleModel', $oModuleModel);
		Context::set('oDocumentModel', $oDocumentModel);

		// 정렬 대상 및 정렬 순서 설정
		$valid_order_targets = ['list_order', 'update_order', 'regdate', 'rand()', 'voted_count', 'readed_count'];
		$order_target = in_array($args->order_target, $valid_order_targets) ? $args->order_target : 'list_order';
		$order_type = $args->order_type === 'asc' ? 'desc' : 'asc';

		// 리스트 개수 설정
		$list_count = isset($args->list_count) ? (int) $args->list_count : 10;

		// 설정값 기본값 지정
		$defaults = [
			'subject_cut_size' => 0,
			'content_cut_size' => 600,
			'thumbnail_type' => 'fill',
			'thumbnail_width' => 330,
			'thumbnail_height' => 300,
			'top_title_size' => '24px',
			'sum_font_size' => '14px',
			'title_color' => '#ff9031',
			'sum_font_color' => '#444',
			'title_font_color' => '#444',
			'content_l_h' => '1.5',
			'content_h_n' => 6,
			'back_color' => 'transparent',
			'speed' => 5000,
			'info_area' => 'info',
			'sname' => 'start',
			'ename' => 'end',
			'end_title' => '종료',
			'approach_title' => '종료임박',
			'today_title' => '오늘종료',
			'a_d' => '+1 day',
			'i_d' => '-1 days',
			'duration_new' => 24
		];

		foreach ($defaults as $key => $value) {
			if (!isset($args->$key)) {
				$args->$key = $value;
			}
		}

		// 대상 모듈 처리
		$module_srls = isset($args->module_srls) ? explode(',', $args->module_srls) : [];
		if (!empty($args->mid_list)) {
			$mid_list = explode(',', $args->mid_list);
			$module_srls = $oModuleModel->getModuleSrlByMid($mid_list);
		}

		// 문서 목록 가져오기
		$query_params = [
			'module_srl' => implode(',', (array) $module_srls),
			'category_srl' => $args->category_srl,
			'sort_index' => $order_target,
			'order_type' => $order_type,
			'list_count' => $list_count
		];
		$output = executeQueryArray('widgets.mh_bigslider.getMhMulti', $query_params);
		if (!$output->toBool()) return;

		$document_list = [];
		foreach ($output->data ?? [] as $attribute) {
			$oDocument = new documentItem();
			$oDocument->setAttribute($attribute);
			$oModuleInfo = $oModuleModel->getModuleInfoByModuleSrl($attribute->module_srl);
			$oDocument->mid = $oModuleInfo->mid;
			$document_list[] = $oDocument;
		}

		// 템플릿에 데이터 전달
		$args->document_list = $document_list;
		Context::set('box_list', $output->data);
		Context::set('widget_info', $args);

		// 템플릿 파일 설정
		$tpl_path = sprintf('%sskins/%s', $this->widget_path, $args->skin);
		$tpl_file = 'list';
		$oTemplate = TemplateHandler::getInstance();
		return $oTemplate->compile($tpl_path, $tpl_file);
	}
}