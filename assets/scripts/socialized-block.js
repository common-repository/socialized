(function(blocks, element, blockEditor) {


    const { __ } = window.wp.i18n;
    const el = element.createElement;
    const useBlockProps = blockEditor.useBlockProps;
    const useInnerBlocksProps = blockEditor.useInnerBlocksProps;
    const InnerBlocks = blockEditor.InnerBlocks;

    // Documentation: https://developer.wordpress.org/block-editor/reference-guides/block-api/block-registration/
    blocks.registerBlockType('aurise/socialized', {
        /**
         * Block Backend
         *
         * Outputs the block attributes form in the editor
         *
         * @param mixed props
         * @param mixed setAttributes
         * @param mixed className
         *
         * @return object React element object
         */
        edit: function(props, setAttributes, className) {
            var blockProps = useBlockProps(),
                innerBlockProps = useInnerBlocksProps(blockProps), // Unused, but function needs to run
                output = el('div', blockProps, el('div', { className: 'aurise-plugin aurise-socialized' },
                    // Row with Block Title
                    el(
                        'p', null, __('The social sharing buttons from Socialized preview will update momentarily…')
                    )
                ));
            jQuery('.aurise-plugin.aurise-socialized').html(window["socialized_preview_html"]);
            return output;
        },

        /**
         * Block Frontend
         *
         * Outputs the dynamic contents of the block
         */
        save: function(props) {
            return InnerBlocks.Content;
        }
    });

})(
    window.wp.blocks,
    window.wp.element,
    window.wp.blockEditor
);