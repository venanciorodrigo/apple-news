<?php
namespace Exporter\Markdown;

/**
 * This class transforms HTML into Article Format Markdown, which is a subset
 * of Markdown.
 *
 * For elements that are not supported, just skip them and add the contents of
 * the tag.
 *
 * @since 0.0.0
 */
class Markdown {

	private $list_mode;
	private $list_index;

	function __construct() {
		$this->list_mode = 'ul';
		$this->list_index = 1;
	}

	/**
	 * Transforms HTML into Article Format Markdown.
	 */
	public function parse( $html ) {
		// PHP's DomDocument doesn't like HTML5 so we must ignore errors, we'll
		// manually handle all tags anyways.
		$dom = new \DOMDocument();
		libxml_use_internal_errors( true );
		// A trick to load string as UTF-8
		$dom->loadHTML( '<?xml encoding="UTF-8">' . $html );
		libxml_clear_errors( true );

		// Find the first-level nodes of the body tag.
		$nodes = $dom->getElementsByTagName( 'body' )->item( 0 )->childNodes;

		// Parse them and return result
		return $this->parseNodes( $nodes );
	}

	private function parseNodes( $nodes ) {
		$result = '';
		foreach ( $nodes as $node ) {
			$result .= $this->parseNode( $node );
		}

		return $result;
	}

	private function parseNode( $node ) {
		var_dump( 'parse: ' . $node->nodeName );
		switch( $node->nodeName ) {
		case '#text':
			return $this->parseTextNode( $node );
		case 'strong':
			return $this->parseStrongNode( $node );
		case  'i':
		case 'em':
			return $this->parseEmphasisNode( $node );
		case 'br':
			return $this->parseLinebreakNode( $node );
		case 'p':
			return $this->parseParagraphNode( $node );
		case 'a':
			return $this->parseHyperlinkNode( $node );
		case 'ul':
			return $this->parseUnorderedListNode( $node );
		case 'ol':
			return $this->parseOrderedListNode( $node );
		case 'li':
			return $this->parseListItemNode( $node );
		}

		return $node->nodeValue ?: '';
	}

	private function parseTextNode( $node ) {
		return $node->nodeValue;
	}

	private function parseLinebreakNode( $node ) {
		return "  \n";
	}

	private function parseStrongNode( $node ) {
		return '**' . $this->parseNodes( $node->childNodes ) . '**';
	}

	private function parseEmphasisNode( $node ) {
		return '_' . $this->parseNodes( $node->childNodes ) . '_';
	}

	private function parseParagraphNode( $node ) {
		return $this->parseNodes( $node->childNodes ) . "\n\n";
	}

	/**
	 * Hyperlinks are not yet supported in Article Format markdown. Ignore for
	 * now.
	 */
	private function parseHyperlinkNode( $node ) {
		$url = $node->getAttribute( 'href' );
		return '[' . $this->parseNodes( $node->childNodes ) . '](' . $url . ')';
	}

	private function parseUnorderedListNode( $node ) {
		$this->list_mode = 'ul';
		return $this->parseNodes( $node->childNodes ) . "\n";
	}

	private function parseOrderedListNode( $node ) {
		$this->list_mode = 'ol';
		$this->list_index = 1;
		return $this->parseNodes( $node->childNodes ) . "\n";
	}

	private function parseListItemNode( $node ) {
		if( 'ol' == $this->list_mode ) {
			return $this->list_index . '. ' . $this->parseNodes( $node->childNodes );
			$this->list_index += 1;
		}

		return "- " . $this->parseNodes( $node->childNodes );
	}

}
