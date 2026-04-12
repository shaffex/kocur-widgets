//
//  Modifier_hideKeyboardOnTap.swift
//  Remote Widget
//
//  Created by Peter Popovec on 12/04/2026.
//

import MagicUiFramework
import SwiftUI

extension View {
    func hideKeyboardOnTap() -> some View {
        modifier(HideKeyboardOnTap())
    }
}

private struct HideKeyboardOnTap: ViewModifier {
    func body(content: Content) -> some View {
        content
            .contentShape(Rectangle())
            .onTapGesture {
                UIApplication.shared.sendAction(
                    #selector(UIResponder.resignFirstResponder),
                    to: nil,
                    from: nil,
                    for: nil
                )
            }
    }
}

struct Modifier_hideKeyboardOnTap: SxModifierProtocol {
    @DynamicNode var node: MagicNode
    
    func body(content: Content) -> some View {
        content.hideKeyboardOnTap()
    }
}
