//
//  NewViews.swift
//  Remote Widget
//
//  Created by Peter Popovec on 04/04/2026.
//

import SwiftUI
import MagicUiFramework
import AppIntents

@available(iOS 17.0, *)
struct SxView_OpenUrlIntentButton: SxViewProtocol {
    @DynamicNode var node: MagicNode
    
    var actionName: String {
        node.getAttribute("action") ?? ""
    }
    
    var body: some View {
//        if node.children != nil {
//            Button(intent: ButtonIntent(action: actionName)) {
//                SxChildrenView(view: node.nodeView)
//            }
//        } else {
            //if let url = URL(string: actionName) {
        
        Button(node.getText() ?? "", intent: OpenMapsIntent(url: actionName))
        
                //Button("OPEN URL", intent: OpenURLIntent(URL(string: "maps://")!))
                //Button(node.getText() ?? "", intent: OpenURLIntent(url))
        //}
            
//        }
    }
}

//@available(iOS 17, *)
//public struct ButtonIntent2: AppIntent {
//    //public static var includedPackages: [any AppIntentsPackage.Type] = []
//    public static var title: LocalizedStringResource = "ButtonIntent"
//    public static let isDiscoverable = false
//    public static let openAppWhenRun = false
//
//    @Parameter(title: "Action")
//    var action: String
//    
//    public init() {
//        
//    }
//    
//    init(action: String) {
//        self.action = action
//    }
//    
//    public func perform() async throws -> some IntentResult {
//        print("do pice")
//        OpenURLIntent(<#T##url: URL##URL#>)
//        return .result()
//    }
//}

struct OpenMapsIntent: AppIntent {
    static var title: LocalizedStringResource = "Open Maps"
    
    @Parameter(title: "Url")
    var url: String
    
    init() {}
    
    init(url: String) {
        self.url = url
    }
    
    func perform() async throws -> some IntentResult & OpensIntent {
        return .result(opensIntent: OpenURLIntent(URL(string: url)!))
    }
}
