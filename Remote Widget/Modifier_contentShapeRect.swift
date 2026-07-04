//
//  Modifier_contentShapeRect.swift
//  Remote Widget
//
//  Created by Peter Popovec on 12/04/2026.
//

import MagicUI
import SwiftUI


struct Modifier_contentShapeRect: MagicModifierProvider {
    
    func apply(to view: AnyView, value: String,
                   element: MagicElement, context: MagicContext) -> some View {
        view.contentShape(Rectangle())
    }
}
