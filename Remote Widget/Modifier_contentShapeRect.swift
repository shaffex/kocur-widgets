//
//  Modifier_contentShapeRect.swift
//  Remote Widget
//
//  Created by Peter Popovec on 12/04/2026.
//

import MagicUiFramework
import SwiftUI


struct Modifier_contentShapeRect: SxModifierProtocol {
    @DynamicNode var node: MagicNode
    
    func body(content: Content) -> some View {
        content.contentShape(Rectangle())
    }
}
